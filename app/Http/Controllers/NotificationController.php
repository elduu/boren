<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Notifications\PaymentDueNotification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
class NotificationController extends Controller
{
      public function getUnreadNotifications(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        // Get the authenticated user
        
        // Fetch all unread notifications for the user
        $unreadNotifications = $user->notifications()->whereNull('read_at')->get();

        // Check if there are unread notifications
        if ($unreadNotifications->isEmpty()) {
            return response()->json(['message' => 'No unread notifications'], 200);
        }

        // Return unread notifications
        return response()->json($unreadNotifications, 200);
    }
public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ], 200);
    }

    /**
     * Get unread notifications for the authenticated user.
     */
    // public function unread(Request $request)
    // {
    //     $user = $request->user();
    //     $unreadNotifications = $user->unreadNotifications()->latest()->get();

    //     return response()->json([
    //         'success' => true,
    //         'notifications' => $unreadNotifications,
    //     ], 200);
    // }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ], 200);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ], 200);
    }
    public function countUnreadNotifications(Request $request)
{
    $user = $request->user();// Get the authenticated user
    $lastExecuted = Cache::get('last_notif_execution', now()->subDay()); // Default to a day ago

        // Execute commands if it's been more than 24 hours
        if ($lastExecuted->diffInHours(now()) >= 24) {
            // Execute the Artisan commands
            Artisan::call('app:send-contract-renewal-notifications');
            Artisan::call('app:send-payment-due-notifications');
    
            // Update the last execution time
            Cache::put('last_notif_execution', now());
        }
    // Count the unread notifications for the user
    $unreadNotificationsCount = $user->unreadNotifications()->count();

    return response()->json(['unread_notifications_count' => $unreadNotificationsCount], 200);
}
public function listContractRenewalNotifications(Request $request)
{
    $user = $request->user();// Get the authenticated user

    // Get all ContractRenewalNotifications for the user
    $contractRenewalNotifications = $user->notifications()
        ->where('type', 'App\Notifications\ContractRenewalNotification')
        ->get();

    // Count the unread ContractRenewalNotifications
    $unreadContractRenewalNotificationsCount = $contractRenewalNotifications->whereNull('read_at')->count();

    return response()->json([
        'contract_renewal_notifications' => $contractRenewalNotifications,
        'unread_contract_renewal_notifications_count' => $unreadContractRenewalNotificationsCount,
    ], 200);
}
public function listPaymentDueNotifications(Request $request)
{
    $user = $request->user(); // Get the authenticated user

    // Get all PaymentDueNotifications for the user
    $paymentDueNotifications = $user->notifications()
        ->where('type', 'App\Notifications\PaymentDueNotification')
        ->get();

    // Count the unread PaymentDueNotifications
    $unreadPaymentDueNotificationsCount = $paymentDueNotifications->whereNull('read_at')->count();

    return response()->json([
        'payment_due_notifications' => $paymentDueNotifications,
        'unread_payment_due_notifications_count' => $unreadPaymentDueNotificationsCount,
    ], 200);
}

}
