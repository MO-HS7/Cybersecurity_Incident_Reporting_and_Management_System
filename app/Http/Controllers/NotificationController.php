<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Class NotificationController
 *
 * Handles user notifications including listing, fetching,
 * marking as read, and deletion (single/all).
 *
 * @package App\Http\Controllers
 */
class NotificationController extends Controller
{
    /**
     * Display a paginated list of notifications for the authenticated user.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->latest()
            ->paginate(10);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Retrieve the latest 10 notifications with additional metadata as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotifications()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'default',
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'severity' => $data['severity'] ?? 'medium',
                    'icon' => $data['icon'] ?? '📄',
                    'color' => $data['color'] ?? 'gray',
                    'action_url' => $data['action_url'] ?? null,
                    'priority' => $data['priority'] ?? 'normal',
                    'created_at' => $notification->created_at->diffForHumans(),
                    'read_at' => $notification->read_at,
                    'is_read' => !is_null($notification->read_at)
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        try {
            $notification = $user->notifications()->where('id', $id)->first();
            
            if ($notification && !$notification->read_at) {
                $notification->markAsRead();
                Log::info('Notification marked as read:', ['id' => $id, 'user_id' => $user->id]);
                return redirect()->back()->with('success', 'Notification marked as read');
            } else {
                return redirect()->back()->with('info', 'Notification was already read or not found');
            }
            
        } catch (\Exception $e) {
            Log::error('Error marking notification as read:', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to mark notification as read');
        }
    }

    /**
     * Mark all notifications for the authenticated user as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        try {
            $unreadNotifications = $user->unreadNotifications;
            $count = $unreadNotifications->count();
            
            if ($count > 0) {
                $unreadNotifications->markAsRead();
                Log::info('All notifications marked as read:', ['user_id' => $user->id, 'count' => $count]);
                return redirect()->back()->with('success', "$count notifications marked as read");
            } else {
                return redirect()->back()->with('info', 'No unread notifications');
            }
            
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read:', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to mark all notifications as read');
        }
    }

    /**
     * Delete a specific notification.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        try {
            $notification = $user->notifications()->where('id', $id)->first();
            
            if ($notification) {
                $notification->delete();
                Log::info('Notification deleted:', ['id' => $id, 'user_id' => $user->id]);
                return redirect()->back()->with('success', 'Notification deleted successfully');
            } else {
                return redirect()->back()->with('error', 'Notification not found');
            }
            
        } catch (\Exception $e) {
            Log::error('Error deleting notification:', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete notification');
        }
    }

    /**
     * Delete all notifications for the authenticated user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAll()
    {
        $user = Auth::user();
        
        try {
            $count = $user->notifications()->count();
            
            if ($count > 0) {
                $user->notifications()->delete();
                Log::info('All notifications deleted:', ['user_id' => $user->id, 'count' => $count]);
                return redirect()->back()->with('success', "$count notifications deleted successfully");
            } else {
                return redirect()->back()->with('info', 'No notifications to delete');
            }
            
        } catch (\Exception $e) {
            Log::error('Error deleting all notifications:', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete all notifications');
        }
    }
}
