<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseNotifications extends Command
{
    protected $signature = 'notifications:diagnose';
    protected $description = 'Diagnose notification system for duplicates and issues';

    public function handle()
    {
        $this->info('🔍 Starting Notification System Diagnosis...');
        $this->newLine();

        // 1. Check for duplicate notifications
        $this->checkDuplicateNotifications();
        
        // 2. Check notification distribution
        $this->checkNotificationDistribution();
        
        // 3. Check for orphaned notifications
        $this->checkOrphanedNotifications();
        
        // 4. Check notification performance
        $this->checkNotificationPerformance();
        
        // 5. Check broadcasting configuration
        $this->checkBroadcastingConfig();

        $this->newLine();
        $this->info('✅ Diagnosis completed!');
    }

    private function checkDuplicateNotifications()
    {
        $this->info('1. Checking for duplicate notifications...');
        
        // Find potential duplicates based on same user, title, and notifiable
        $duplicates = DB::select("
            SELECT 
                user_id, 
                title, 
                notifiable_id, 
                notifiable_type, 
                COUNT(*) as count,
                GROUP_CONCAT(id) as notification_ids,
                MIN(created_at) as first_created,
                MAX(created_at) as last_created
            FROM notifications 
            WHERE user_id IS NOT NULL
            GROUP BY user_id, title, notifiable_id, notifiable_type 
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ");

        if (empty($duplicates)) {
            $this->line('   ✅ No duplicate notifications found');
        } else {
            $this->warn("   ⚠️  Found " . count($duplicates) . " sets of duplicate notifications:");
            
            foreach ($duplicates as $duplicate) {
                $this->line("   - User ID: {$duplicate->user_id}, Title: '{$duplicate->title}', Count: {$duplicate->count}");
                $this->line("     Notification IDs: {$duplicate->notification_ids}");
                $this->line("     Time span: {$duplicate->first_created} to {$duplicate->last_created}");
                $this->newLine();
            }
        }

        // Check for role-based duplicates
        $roleDuplicates = DB::select("
            SELECT 
                target_role, 
                title, 
                notifiable_id, 
                notifiable_type, 
                COUNT(*) as count,
                GROUP_CONCAT(id) as notification_ids
            FROM notifications 
            WHERE target_role IS NOT NULL
            GROUP BY target_role, title, notifiable_id, notifiable_type 
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ");

        if (!empty($roleDuplicates)) {
            $this->warn("   ⚠️  Found " . count($roleDuplicates) . " sets of role-based duplicate notifications:");
            
            foreach ($roleDuplicates as $duplicate) {
                $roleName = $this->getRoleName($duplicate->target_role);
                $this->line("   - Role: {$roleName}, Title: '{$duplicate->title}', Count: {$duplicate->count}");
                $this->line("     Notification IDs: {$duplicate->notification_ids}");
                $this->newLine();
            }
        }
    }

    private function checkNotificationDistribution()
    {
        $this->info('2. Checking notification distribution...');
        
        $stats = DB::select("
            SELECT 
                target_role,
                COUNT(*) as total_notifications,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_notifications,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
            FROM notifications 
            GROUP BY target_role
        ");

        foreach ($stats as $stat) {
            $roleName = $this->getRoleName($stat->target_role);
            $this->line("   {$roleName}: {$stat->total_notifications} total ({$stat->unread_notifications} unread, {$stat->read_notifications} read)");
        }

        // Check notification types
        $typeStats = DB::select("
            SELECT 
                notifiable_type,
                COUNT(*) as count
            FROM notifications 
            WHERE notifiable_type IS NOT NULL
            GROUP BY notifiable_type
            ORDER BY count DESC
        ");

        $this->newLine();
        $this->line('   Notification types:');
        foreach ($typeStats as $type) {
            $this->line("   - {$type->notifiable_type}: {$type->count}");
        }
    }

    private function checkOrphanedNotifications()
    {
        $this->info('3. Checking for orphaned notifications...');
        
        // Check for notifications with invalid user_id
        $orphanedUsers = DB::select("
            SELECT COUNT(*) as count 
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id 
            WHERE n.user_id IS NOT NULL AND u.id IS NULL
        ");

        if ($orphanedUsers[0]->count > 0) {
            $this->warn("   ⚠️  Found {$orphanedUsers[0]->count} notifications with invalid user references");
        } else {
            $this->line('   ✅ No orphaned user notifications found');
        }

        // Check for notifications with invalid notifiable references
        $orphanedNotifiables = DB::select("
            SELECT 
                notifiable_type,
                COUNT(*) as count
            FROM notifications 
            WHERE notifiable_id IS NOT NULL 
            AND notifiable_type IS NOT NULL
            GROUP BY notifiable_type
        ");

        foreach ($orphanedNotifiables as $notifiable) {
            $tableName = $this->getTableNameFromModel($notifiable->notifiable_type);
            if ($tableName) {
                $exists = DB::select("
                    SELECT COUNT(*) as valid_count
                    FROM notifications n
                    LEFT JOIN {$tableName} t ON n.notifiable_id = t.id
                    WHERE n.notifiable_type = ? AND t.id IS NULL
                ", [$notifiable->notifiable_type]);
                
                if ($exists[0]->valid_count > 0) {
                    $this->warn("   ⚠️  Found {$exists[0]->valid_count} notifications with invalid {$notifiable->notifiable_type} references");
                }
            }
        }
    }

    private function checkNotificationPerformance()
    {
        $this->info('4. Checking notification performance...');
        
        $totalNotifications = Notification::count();
        $this->line("   Total notifications: {$totalNotifications}");
        
        // Check recent notification creation rate
        $recentNotifications = Notification::where('created_at', '>=', now()->subDays(7))->count();
        $this->line("   Notifications in last 7 days: {$recentNotifications}");
        
        // Check average notifications per user
        $avgPerUser = DB::select("
            SELECT AVG(notification_count) as avg_count
            FROM (
                SELECT user_id, COUNT(*) as notification_count
                FROM notifications 
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) as user_counts
        ");
        
        if (!empty($avgPerUser)) {
            $this->line("   Average notifications per user: " . round($avgPerUser[0]->avg_count, 2));
        }
        
        // Check for users with excessive notifications
        $heavyUsers = DB::select("
            SELECT user_id, COUNT(*) as count
            FROM notifications 
            WHERE user_id IS NOT NULL
            GROUP BY user_id 
            HAVING COUNT(*) > 100
            ORDER BY count DESC
            LIMIT 5
        ");
        
        if (!empty($heavyUsers)) {
            $this->line('   Users with most notifications:');
            foreach ($heavyUsers as $user) {
                $this->line("   - User ID {$user->user_id}: {$user->count} notifications");
            }
        }
    }

    private function checkBroadcastingConfig()
    {
        $this->info('5. Checking broadcasting configuration...');
        
        $broadcastDriver = config('broadcasting.default');
        $this->line("   Broadcast driver: {$broadcastDriver}");
        
        if ($broadcastDriver === 'null') {
            $this->warn('   ⚠️  Broadcasting is disabled (driver: null)');
            $this->line('   💡 Consider configuring Pusher or Redis for real-time notifications');
        } else {
            $this->line('   ✅ Broadcasting is configured');
        }
        
        // Check if queue is configured
        $queueDriver = config('queue.default');
        $this->line("   Queue driver: {$queueDriver}");
        
        if ($queueDriver === 'sync') {
            $this->warn('   ⚠️  Queue is running synchronously');
            $this->line('   💡 Consider using Redis or database queue for better performance');
        }
    }

    private function getRoleName($role)
    {
        return match($role) {
            0 => 'Admin',
            1 => 'Driver', 
            2 => 'Shop',
            default => 'Unknown'
        };
    }

    private function getTableNameFromModel($modelClass)
    {
        return match($modelClass) {
            'App\Models\Order' => 'orders',
            'App\Models\User' => 'users',
            'App\Models\Complaint' => 'complaints',
            default => null
        };
    }
}
