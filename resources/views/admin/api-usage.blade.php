@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        📊 OpenWeatherMap API Usage Monitor
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        Monitor your API usage until January 29, 2026 deadline
    </p>
</div>

<!-- API Usage Stats -->
<div class="tea-card p-6 mb-8">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
        📈 Today's Usage Statistics
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Daily Calls -->
        <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
            <div class="text-3xl font-bold mb-2" style="color: var(--accent-green);">
                {{ $stats['daily_calls'] }}
            </div>
            <p class="text-sm" style="color: var(--text-medium);">Daily API Calls</p>
        </div>
        
        <!-- Daily Limit -->
        <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
            <div class="text-3xl font-bold mb-2" style="color: var(--primary-green);">
                {{ $stats['daily_limit'] }}
            </div>
            <p class="text-sm" style="color: var(--text-medium);">Daily Limit</p>
        </div>
        
        <!-- Usage Percentage -->
        <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
            <div class="text-3xl font-bold mb-2" 
                 style="color: {{ $stats['daily_percentage'] >= 90 ? 'var(--danger-red)' : 
                                   ($stats['daily_percentage'] >= 70 ? 'var(--warning-orange)' : 'var(--accent-green)') }};">
                {{ $stats['daily_percentage'] }}%
            </div>
            <p class="text-sm" style="color: var(--text-medium);">Usage Percentage</p>
        </div>
        
        <!-- Days Until Deadline -->
        <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
            <div class="text-3xl font-bold mb-2" 
                 style="color: {{ $stats['days_until_jan31'] <= 3 ? 'var(--danger-red)' : 
                                   ($stats['days_until_jan31'] <= 7 ? 'var(--warning-orange)' : 'var(--accent-green)') }};">
                {{ $stats['days_until_jan31'] }}
            </div>
            <p class="text-sm" style="color: var(--text-medium);">Days Until Jan 31</p>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex justify-between mb-2">
            <span class="text-sm font-medium" style="color: var(--text-medium);">Daily Usage Progress</span>
            <span class="text-sm font-medium" style="color: var(--text-medium);">
                {{ $stats['daily_calls'] }} / {{ $stats['daily_limit'] }} calls
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="h-4 rounded-full transition-all duration-300" 
                 style="width: {{ $stats['daily_percentage'] }}%; 
                        background: {{ $stats['daily_percentage'] >= 90 ? 'var(--danger-red)' : 
                                     ($stats['daily_percentage'] >= 70 ? 'var(--warning-orange)' : 'var(--accent-green)') }};">
            </div>
        </div>
    </div>
    
    <!-- Recommendation -->
    <div class="p-4 rounded-lg" 
         style="background: {{ $stats['days_until_jan31'] <= 1 ? 'var(--danger-red)' : 
                           ($stats['days_until_jan31'] <= 3 ? 'var(--warning-orange)' : 
                           ($stats['daily_percentage'] >= 90 ? 'var(--warning-orange)' : 'var(--light-green)') }};">
        <p class="text-sm font-medium text-white">
            📋 <strong>Recommendation:</strong> {{ $stats['recommendation'] }}
        </p>
    </div>
</div>

<!-- Cache Settings -->
<div class="tea-card p-6 mb-8">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
        ⚙️ Cache Settings
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="font-semibold mb-3" style="color: var(--text-dark);">Current Configuration</h3>
            <div class="space-y-2">
                <div class="flex justify-between p-3 rounded" style="background: var(--cream-green);">
                    <span style="color: var(--text-medium);">Cache Duration</span>
                    <span class="font-medium" style="color: var(--accent-green);">
                        {{ $stats['cache_duration_minutes'] }} minutes
                    </span>
                </div>
                <div class="flex justify-between p-3 rounded" style="background: var(--cream-green);">
                    <span style="color: var(--text-medium);">Safety Buffer</span>
                    <span class="font-medium" style="color: var(--accent-green);">80% of limit</span>
                </div>
                <div class="flex justify-between p-3 rounded" style="background: var(--cream-green);">
                    <span style="color: var(--text-medium);">Conservative Mode</span>
                    <span class="font-medium" style="color: {{ $stats['is_conservative_mode'] ? 'var(--warning-orange)' : 'var(--accent-green)' }};">
                        {{ $stats['is_conservative_mode'] ? 'Active (last 3 days)' : 'Normal' }}
                    </span>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="font-semibold mb-3" style="color: var(--text-dark);">Cache Benefits</h3>
            <div class="space-y-2 text-sm" style="color: var(--text-light);">
                <p>✅ Reduces API calls by {{ 100 - (100 / $stats['cache_duration_minutes']) }}%</p>
                <p>✅ Faster response times for users</p>
                <p>✅ Protects against rate limits</p>
                <p>✅ Ensures service until Jan 29</p>
                <p>✅ Automatic fallback to cached data</p>
            </div>
        </div>
    </div>
</div>

<!-- Timeline to Deadline -->
<div class="tea-card p-6 mb-8">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
        📅 Timeline to January 31, 2026
    </h2>
    
    <div class="space-y-4">
        @if($stats['days_until_jan31'] <= 0)
            <div class="p-4 rounded-lg border-l-4" style="background: var(--danger-red); border-color: var(--danger-red);">
                <h3 class="font-bold text-white mb-2">🚨 Deadline Passed</h3>
                <p class="text-white">The January 31 deadline has passed. System is using cached data only.</p>
            </div>
        @elseif($stats['days_until_jan31'] <= 1)
            <div class="p-4 rounded-lg border-l-4" style="background: var(--danger-red); border-color: var(--danger-red);">
                <h3 class="font-bold text-white mb-2">🚨 Last Day - Emergency Mode</h3>
                <p class="text-white">Use cached data exclusively. No API calls recommended.</p>
            </div>
        @elseif($stats['days_until_jan31'] <= 3)
            <div class="p-4 rounded-lg border-l-4" style="background: var(--warning-orange); border-color: var(--warning-orange);">
                <h3 class="font-bold text-white mb-2">⚠️ Conservative Mode Active</h3>
                <p class="text-white">Using 50% of daily limit. Essential updates only.</p>
            </div>
        @elseif($stats['days_until_jan31'] <= 7)
            <div class="p-4 rounded-lg border-l-4" style="background: var(--light-green); border-color: var(--accent-green);">
                <h3 class="font-bold text-white mb-2">⚡ One Week Remaining</h3>
                <p class="text-white">Monitor usage closely. Consider increasing cache duration.</p>
            </div>
        @else
            <div class="p-4 rounded-lg border-l-4" style="background: var(--light-green); border-color: var(--accent-green);">
                <h3 class="font-bold text-white mb-2">✅ Safe Usage Period</h3>
                <p class="text-white">{{ $stats['days_until_jan31'] }} days remaining. Normal usage acceptable.</p>
            </div>
        @endif
        
        <!-- Usage Tips -->
        <div class="mt-6 p-4 rounded" style="background: var(--pale-green);">
            <h4 class="font-semibold mb-3" style="color: var(--text-dark);">💡 Usage Tips:</h4>
            <ul class="text-sm space-y-1" style="color: var(--text-medium);">
                <li>• Cache duration is {{ $stats['cache_duration_minutes'] }} minutes - increases efficiency</li>
                <li>• System automatically falls back to cached data when limits are reached</li>
                <li>• Conservative mode activates automatically in the last 3 days</li>
                <li>• Monitor daily usage to avoid unexpected limit breaches</li>
                <li>• Consider manual cache refresh only when absolutely necessary</li>
            </ul>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="tea-card p-6">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
        🔧 Management Actions
    </h2>
    
    <div class="flex flex-wrap gap-4">
        <form method="POST" action="{{ route('admin.api-usage.clear-cache') }}" class="inline">
            @csrf
            <button type="submit" class="btn-secondary" onclick="return confirm('This will clear API usage statistics. Are you sure?')">
                🗑️ Clear Usage Cache
            </button>
        </form>
        
        <a href="{{ route('top.tea') }}" class="btn-primary">
            🌤️ Test Weather System
        </a>
        
        <a href="{{ route('find.tea') }}" class="btn-primary">
            🔍 Test Find Tea
        </a>
    </div>
    
    <div class="mt-4 p-3 rounded" style="background: var(--cream-green);">
        <p class="text-sm" style="color: var(--text-medium);">
            <strong>Note:</strong> The system automatically protects your API usage through intelligent caching and rate limiting. Manual intervention is rarely needed.
        </p>
    </div>
</div>

@endsection
