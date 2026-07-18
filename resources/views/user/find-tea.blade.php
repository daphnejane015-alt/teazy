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
                        🇲🇾 Your State (Malaysia)
                    </label>
                    <select id="stateSelect" name="state" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);">
                        <option value="">Select State</option>
                        @foreach(\App\Services\WeatherService::getMalaysianStates() as $state => $cities)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block font-semibold mb-2">
                        🏙️ Your City
                    </label>
                    <select id="citySelect" name="city" class="w-full border rounded-lg p-3" style="border-color: var(--border-color);" disabled>
                        <option value="">Select State First</option>
                    </select>
                    <p class="text-xs mt-1" style="color: var(--text-light);">
                        🌏 Cities verified with OpenWeather API • Select your state to see available cities
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

<script>
// Malaysian states with cities data (matching WeatherService)
const malaysianStates = {
    'Kuala Lumpur': ['Kuala Lumpur'],
    'Selangor': ['Shah Alam', 'Petaling Jaya', 'Subang Jaya', 'Klang', 'Ampang', 'Cheras', 'Rawang', 'Kajang', 'Bangi', 'Putrajaya', 'Puchong', 'Damansara', 'Sunway'],
    'Penang': ['George Town', 'Bayan Lepas', 'Bukit Mertajam'],
    'Johor': ['Johor Bahru', 'Batu Pahat', 'Muar', 'Kulai', 'Skudai', 'Kluang'],
    'Perak': ['Ipoh', 'Taiping'],
    'Negeri Sembilan': ['Seremban', 'Port Dickson'],
    'Kedah': ['Alor Setar', 'Sungai Petani'],
    'Kelantan': ['Kota Bharu'],
    'Terengganu': ['Kuala Terengganu'],
    'Sarawak': ['Kuching', 'Miri', 'Sibu', 'Bintulu'],
    'Sabah': ['Kota Kinabalu', 'Sandakan', 'Tawau'],
    'Malacca': ['Malacca'],
    'Pahang': ['Kuantan'],
    'Labuan': ['Labuan']
};

document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('stateSelect');
    const citySelect = document.getElementById('citySelect');

    stateSelect.addEventListener('change', function() {
        const selectedState = this.value;
        
        // Clear city dropdown
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (selectedState && malaysianStates[selectedState]) {
            // Enable city dropdown
            citySelect.disabled = false;
            
            // Populate cities for selected state
            malaysianStates[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        } else {
            // Disable city dropdown if no state selected
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Select State First</option>';
        }
    });
});
</script>
