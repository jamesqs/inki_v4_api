<?php

namespace App\Modules\Messages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Http\Resources\MessageResource;
use App\Modules\Estates\Models\Estate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     * Groups messages by estate and shows latest message per conversation
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all estates where user has messages
        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['estate', 'sender.profilePicture', 'receiver.profilePicture'])
            ->get()
            ->groupBy('estate_id')
            ->map(function ($messages, $estateId) use ($user) {
                $latestMessage = $messages->sortByDesc('created_at')->first();
                $unreadCount = $messages->where('receiver_id', $user->id)
                    ->where('read_at', null)
                    ->count();

                // Determine the other participant
                $otherUserId = $latestMessage->sender_id === $user->id
                    ? $latestMessage->receiver_id
                    : $latestMessage->sender_id;

                $otherUser = $latestMessage->sender_id === $user->id
                    ? $latestMessage->receiver
                    : $latestMessage->sender;

                $estate = $latestMessage->estate;
                $photos = is_array($estate->photos) ? $estate->photos : [];
                $firstPhoto = !empty($photos) ? $photos[0] : null;

                // Get size from custom_attributes
                $customAttributes = is_array($estate->custom_attributes) ? $estate->custom_attributes : [];
                $size = $customAttributes['size'] ?? $customAttributes['terulet'] ?? null;

                return [
                    'estate_id' => $estateId,
                    'estate' => [
                        'id' => $estate->id,
                        'name' => $estate->name,
                        'slug' => $estate->slug,
                        'price' => $estate->price,
                        'currency' => $estate->currency ?? 'HUF',
                        'formatted_price' => $estate->formatted_price ?? number_format($estate->price, 0, ',', ' ') . ' ' . ($estate->currency ?? 'HUF'),
                        'size' => $size,
                        'photo' => $firstPhoto ? [
                            'url' => $firstPhoto['url'] ?? $firstPhoto,
                            'name' => $firstPhoto['name'] ?? null,
                        ] : null,
                    ],
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'profile_picture' => $otherUser->profilePicture ? [
                            'url' => $otherUser->profilePicture->url,
                            'name' => $otherUser->profilePicture->name,
                        ] : null,
                    ],
                    'latest_message' => [
                        'id' => $latestMessage->id,
                        'message' => $latestMessage->message,
                        'created_at' => $latestMessage->created_at->toISOString(),
                        'is_from_me' => $latestMessage->sender_id === $user->id,
                    ],
                    'unread_count' => $unreadCount,
                    'updated_at' => $latestMessage->created_at->toISOString(),
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $conversations,
            'total' => $conversations->count(),
        ]);
    }

    /**
     * Get messages for a specific estate conversation
     */
    public function getMessagesForEstate(Request $request, $estateId): AnonymousResourceCollection
    {
        $user = $request->user();

        // Verify estate exists
        $estate = Estate::findOrFail($estateId);

        // Get all messages for this estate where user is involved
        $messages = Message::forEstate($estateId)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->with(['sender.profilePicture', 'receiver.profilePicture', 'estate'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark all received messages as read
        Message::forEstate($estateId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return MessageResource::collection($messages);
    }

    /**
     * Get conversation between authenticated user and another user for a specific estate
     */
    public function getConversationWith(Request $request, $estateId, $userId): AnonymousResourceCollection
    {
        $currentUser = $request->user();

        // Verify estate exists
        $estate = Estate::findOrFail($estateId);

        // Get messages between these two users for this estate
        $messages = Message::forEstate($estateId)
            ->betweenUsers($currentUser->id, $userId)
            ->with(['sender.profilePicture', 'receiver.profilePicture', 'estate'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark received messages as read
        Message::forEstate($estateId)
            ->where('sender_id', $userId)
            ->where('receiver_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return MessageResource::collection($messages);
    }

    /**
     * Send a message about an estate
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estate_id' => 'required|integer|exists:estates,id',
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|min:1|max:5000',
        ]);

        $sender = $request->user();

        // Verify sender is not sending to themselves
        if ($sender->id === $validated['receiver_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a message to yourself.',
            ], 422);
        }

        // Verify estate exists
        $estate = Estate::findOrFail($validated['estate_id']);

        // Create message
        $message = Message::create([
            'estate_id' => $validated['estate_id'],
            'sender_id' => $sender->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        $message->load(['sender.profilePicture', 'receiver.profilePicture', 'estate']);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => new MessageResource($message),
        ], 201);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Request $request, $messageId): JsonResponse
    {
        $user = $request->user();

        $message = Message::findOrFail($messageId);

        // Verify user is the receiver
        if ($message->receiver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only mark your own received messages as read.',
            ], 403);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
            'data' => new MessageResource($message->fresh(['sender.profilePicture', 'receiver.profilePicture'])),
        ]);
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function markConversationAsRead(Request $request, $estateId, $senderId): JsonResponse
    {
        $user = $request->user();

        // Verify estate exists
        $estate = Estate::findOrFail($estateId);

        // Mark all messages from sender in this estate conversation as read
        $updated = Message::forEstate($estateId)
            ->where('sender_id', $senderId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updated} messages as read",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get unread message count for authenticated user
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Delete a message (soft delete)
     */
    public function deleteMessage(Request $request, $messageId): JsonResponse
    {
        $user = $request->user();

        $message = Message::findOrFail($messageId);

        // Only sender can delete their own messages
        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }
}
