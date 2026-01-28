@extends('layouts.sidebar')
@section('content')

<h1 class="text-3xl font-bold mb-6">Find Tea</h1>

<div class="max-w-2xl mx-auto">
    <div class="card p-8 space-y-6">
        <form method="POST" action="{{ route('find.tea.store') }}" class="space-y-6">
            @csrf

            <!-- Flavor -->
            <div>
                <label class="block font-semibold mb-2">Preferred Flavor</label>
                <select name="flavor" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);" required>
                    <option value="floral">Floral</option>
                    <option value="fruity">Fruity</option>
                    <option value="earthy">Earthy</option>
                    <option value="sweet">Sweet</option>
                    <option value="bitter">Bitter</option>
                    <option value="minty">Minty</option>
                    <option value="any">Any</option>
                </select>
            </div>

            <!-- Caffeine -->
            <div>
                <label class="block font-semibold mb-2">Caffeine Level</label>
                <select name="caffeine" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="caffeine_free">Caffeine Free</option>
                </select>
            </div>

            <!-- Health Goal -->
            <div>
                <label class="block font-semibold mb-2">Health Goal</label>
                <select name="health_goal" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);" required>
                    <option value="relax_calm">Relaxation/Calming</option>
                    <option value="digest">Digestion</option>
                    <option value="stress">Stress Relief</option>
                    <option value="weight_loss">Weight Loss</option>
                    <option value="blood_circulation">Blood Circulation</option>
                    <option value="body_relief">Body Relief</option>
                </select>
            </div>

            <!-- Weather Preferences -->
            <div class="border-t pt-6" style="border-color: var(--border-color);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-dark);">
                    🌤️ Weather-Based Recommendations (Optional)
                </h3>
                
                <!-- Enable Weather Recommendations -->
                <div class="mb-4">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="weather_based_recommendations" value="1" 
                               class="w-5 h-5 rounded" style="accent-color: var(--accent-green);">
                        <span class="font-medium" style="color: var(--text-medium);">
                            Enable weather-based tea recommendations
                        </span>
                    </label>
                    <p class="text-sm mt-2" style="color: var(--text-light);">
                        Get personalized tea suggestions based on your local weather conditions
                    </p>
                </div>

                <!-- Location -->
                <div class="mb-4">
                    <label class="block font-semibold mb-2">
                        🇲🇾 Your City (Malaysia Focus)
                    </label>
                    <input type="text" name="city" placeholder="e.g., Kuala Lumpur, Penang, Johor Bahru" 
                           list="malaysian-cities"
                           class="w-full border rounded-lg p-3" style="border-color: var(--border-color);">
                    <datalist id="malaysian-cities">
                        @foreach(\App\Services\WeatherService::getMalaysianCities() as $city)
                            <option value="{{ $city }}">
                        @endforeach
                    </datalist>
                    <p class="text-xs mt-1" style="color: var(--text-light);">
                        🌏 System optimized for Malaysian cities • Auto-detects Malaysian locations
                    </p>
                </div>

                <!-- Weather Preference -->
                <div>
                    <label class="block font-semibold mb-2">Weather Preference</label>
                    <select name="weather_preference" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);">
                        <option value="auto">🌤️ Auto (Based on current weather)</option>
                        <option value="malaysian_hot_humid">🌴 Malaysian Hot & Humid (28-35°C)</option>
                        <option value="malaysian_rainy">🌧️ Malaysian Rainy Season (Monsoon)</option>
                        <option value="malaysian_haze">🌫️ Malaysian Haze Season (Air Quality)</option>
                        <option value="malaysian_cool_morning">🌅 Malaysian Cool Mornings (18-22°C)</option>
                        <option value="malaysian_afternoon_heat">🌞 Malaysian Afternoon Heat (32-38°C)</option>
                        <option value="malaysian_thunderstorm">⛈️ Malaysian Thunderstorms</option>
                        <option value="malaysian_aircond">❄️ Air-Conditioned Indoors</option>
                    </select>
                    <p class="text-sm mt-2" style="color: var(--text-light);">
                        🇲🇾 Options tailored for Malaysian climate: Hot & Humid, Monsoon, Haze, Cool Mornings, Afternoon Heat, Thunderstorms & Air-Cond environments
                    </p>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Get Recommendation
            </button>
        </form>
    </div>
</div>

@endsection
