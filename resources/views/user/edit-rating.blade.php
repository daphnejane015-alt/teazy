@extends('layouts.sidebar')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('rated.tea') }}" class="btn-secondary inline-flex items-center">
            ← Back to My Rated Teas
        </a>
    </div>

    <div class="tea-card p-8">
        <h1 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
            📝 Edit Your Rating
        </h1>

        <form action="{{ route('rated.tea.update', $rating->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Tea Information -->
            <div class="mb-6 p-4 rounded-lg" style="background: var(--cream-green);">
                <div class="flex items-center space-x-4">
                    @php
                        $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                        $imgSrc = $rating->tea->image
                            ? (str_starts_with($rating->tea->image, 'http') ? $rating->tea->image
                                : (str_starts_with($rating->tea->image, '//') ? 'https:'.$rating->tea->image
                                : (str_starts_with($rating->tea->image, '/storage/') ? $rating->tea->image : '/storage/'.$rating->tea->image)))
                            : $fallbackImage;
                    @endphp
                    
                    <img src="{{ $imgSrc }}" 
                         class="w-20 h-20 object-cover rounded-lg" 
                         alt="{{ $rating->tea->name }}">
                    
                    <div class="flex-1">
                        <h3 class="text-lg font-bold" style="color: var(--text-dark);">
                            {{ $rating->tea->name }}
                        </h3>
                        <p class="text-sm" style="color: var(--text-medium);">
                            🍃 {{ $rating->tea->flavor }} • ⚡ {{ $rating->tea->caffeine_level }}
                        </p>
                        <p class="text-sm line-clamp-2" style="color: var(--text-light);">
                            {{ $rating->tea->health_benefit }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Rating Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium mb-3" style="color: var(--text-dark);">
                    ⭐ Your Rating
                </label>
                <div class="flex items-center space-x-4">
                    <select name="rating" class="search-bar flex-1" required>
                        <option value="">Select rating</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ $rating->rating == $i ? 'selected' : '' }}>
                                {{ $i }} ⭐ @if($i == 1) Poor @elseif($i == 2) Fair @elseif($i == 3) Good @elseif($i == 4) Very Good @else Excellent @endif
                            </option>
                        @endfor
                    </select>
                    
                    <div class="flex space-x-1">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-2xl cursor-pointer hover:scale-110 transition-transform" 
                                  onclick="document.querySelector('select[name=rating]').value = '{{ $i }}'"
                                  style="color: {{ $i <= ($rating->rating ?? 0) ? 'var(--light-green)' : 'var(--text-light)' }};">
                                ⭐
                            </span>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Description Box -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium mb-3" style="color: var(--text-dark);">
                    📝 Your Notes (Optional)
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="4" 
                    class="w-full search-bar resize-none"
                    placeholder="Add your personal notes about this tea... How did it make you feel? When do you like to drink it? Any brewing tips?"
                    maxlength="500">{{ $rating->description ?? '' }}</textarea>
                <div class="text-right mt-1">
                    <span class="text-xs" style="color: var(--text-light);">
                        <span id="charCount">{{ strlen($rating->description ?? '') }}</span>/500 characters
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4">
                <button type="submit" class="btn-primary flex-1">
                    💾 Update Rating
                </button>
                
                <a href="{{ route('rated.tea') }}" class="btn-secondary flex-1 text-center">
                    Cancel
                </a>
            </div>
        </form>

        <!-- Delete Button -->
        <div class="mt-6 pt-6 border-t" style="border-color: var(--border-color);">
            <form action="{{ route('rated.tea.destroy', $rating->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this rating? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full btn-secondary text-red-500 hover:bg-red-50 border-red-200">
                    🗑️ Delete Rating
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    
    textarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
});
</script>

@endsection
