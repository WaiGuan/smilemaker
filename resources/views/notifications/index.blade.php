@extends('layouts.app')

@section('title', 'Notifications - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-bell me-2"></i>Notifications</h2>
    <div>
        @if($notifications->count() > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-check-double me-2"></i>Mark All as Read
                </button>
            </form>
        @endif
    </div>
</div>

@if($notifications->count() > 0)
    <div class="list-group">
        @foreach($notifications as $notification)
            <div class="list-group-item {{ !$notification->is_read ? 'bg-light' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            @if(!$notification->is_read)
                                <span class="badge bg-primary me-2">New</span>
                            @endif
                            <h6 class="mb-0">{{ $notification->message }}</h6>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            @if(!$notification->is_read)
                                <li>
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-check me-2"></i>Mark as Read
                                        </button>
                                    </form>
                                </li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"
                                            onclick="return confirm('Are you sure you want to delete this notification?')">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $notifications->links() }}
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No notifications</h4>
        <p class="text-muted">You don't have any notifications yet.</p>
    </div>
@endif
@endsection
