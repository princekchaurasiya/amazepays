<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class UserBlockController extends Controller
{
    public function blockUser(Request $request)
    {
        try {
            $request->validate([
                'mobile' => 'required|regex:/^[0-9]{10}$/',
                'restriction_type' => 'required|in:login,transaction,feature',
                'features' => 'required_if:restriction_type,feature',
                'reason' => 'required|string|max:255',
            ]);

            $user = User::where('mobile', $request->mobile)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Apply the appropriate restriction based on type
            switch ($request->restriction_type) {
                case 'login':
                    $user->is_blocked = true;
                    break;
                case 'transaction':
                    $user->can_transact = false;
                    break;
                case 'feature':
                    $features = $request->features;
                    if (is_string($features)) {
                        $features = json_decode($features, true);
                    }
                    $user->restricted_features = json_encode($features);
                    break;
            }

            // Set the restriction reason
            $user->restriction_reason = $request->reason;
            $user->save();

            Log::info('User restriction applied', [
                'user_id' => $user->id, 
                'mobile' => $user->mobile,
                'restriction_type' => $request->restriction_type,
                'features' => $request->restriction_type === 'feature' ? $features : null
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User restriction applied successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error applying user restriction', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to apply user restriction'
            ], 500);
        }
    }

    public function unblockUser(Request $request)
    {
        try {
            $request->validate([
                'mobile' => 'required|regex:/^[0-9]{10}$/',
                'restriction_type' => 'required|in:login,transaction,feature',
            ]);

            $user = User::where('mobile', $request->mobile)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Remove the appropriate restriction based on type
            switch ($request->restriction_type) {
                case 'login':
                    $user->is_blocked = false;
                    break;
                case 'transaction':
                    $user->can_transact = true;
                    break;
                case 'feature':
                    $user->restricted_features = null;
                    break;
            }

            // Clear the restriction reason if all restrictions are removed
            if (!$user->is_blocked && $user->can_transact && !$user->restricted_features) {
                $user->restriction_reason = null;
            }

            $user->save();

            Log::info('User restriction removed', [
                'user_id' => $user->id, 
                'mobile' => $user->mobile,
                'restriction_type' => $request->restriction_type
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User restriction removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing user restriction', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove user restriction'
            ], 500);
        }
    }

    public function checkUserRestrictions(Request $request)
    {
        try {
            // If mobile is provided, check specific user
            if ($request->has('mobile')) {
                $user = User::where('mobile', $request->mobile)->first();
                if (!$user) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User not found'
                    ], 404);
                }
                
                return response()->json([
                    'status' => 'success',
                    'user' => [
                        'name' => $user->name,
                        'mobile' => $user->mobile,
                        'is_blocked' => $user->is_blocked,
                        'can_transact' => $user->can_transact,
                        'restricted_features' => $user->restricted_features,
                        'restriction_reason' => $user->restriction_reason,
                        'updated_at' => $user->updated_at
                    ]
                ]);
            } 
            // If feature is provided, check users with that feature restriction
            else if ($request->has('feature')) {
                $users = User::whereNotNull('restricted_features')
                    ->where('restricted_features', 'like', '%' . $request->feature . '%')
                    ->select('name', 'mobile', 'restricted_features', 'restriction_reason', 'updated_at')
                    ->get();
                
                return response()->json([
                    'status' => 'success',
                    'users' => $users
                ]);
            }
            // Otherwise, return all users with any restrictions
            else {
                $users = User::where(function($query) {
                    $query->where('is_blocked', true)
                        ->orWhere('can_transact', false)
                        ->orWhereNotNull('restricted_features');
                })
                ->select('name', 'mobile', 'is_blocked', 'can_transact', 'restricted_features', 'restriction_reason', 'updated_at')
                ->get();
                
                return response()->json([
                    'status' => 'success',
                    'users' => $users
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in checkUserRestrictions method', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while checking user restrictions'
            ], 500);
        }
    }
}
