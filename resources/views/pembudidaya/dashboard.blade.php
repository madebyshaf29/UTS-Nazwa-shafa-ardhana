@extends('layouts.pembudidaya')

@section('title', 'Beranda')
@section('subtitle', 'Kelola data usaha budidaya perikanan Anda')

@section('content')
    <!-- Weather Widget Section -->
    <div class="mb-8 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-[2rem] p-8 text-white shadow-xl relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col lg:flex-row justify-between items-center gap-8">
            <div class="text-center lg:text-left">
                <div class="flex flex-col md:flex-row items-center gap-3 mb-2 justify-center lg:justify-start">
                    <p class="text-blue-100 font-bold uppercase tracking-[0.2em] text-xs">Informasi Cuaca Terkini</p>
                    <select id="location-selector" onchange="changeLocation()" class="bg-white/20 backdrop-blur-md border border-white/20 rounded-full px-4 py-1 text-[10px] font-bold text-white outline-none cursor-pointer hover:bg-white/30 transition-all">
                        <option value="-7.4244,109.2303" class="text-gray-800">Pusat Purwokerto</option>
                        <option value="-7.4042,109.2486" class="text-gray-800">Purwokerto Utara</option>
                        <option value="-7.4475,109.2433" class="text-gray-800">Purwokerto Selatan</option>
                        <option value="-7.4261,109.2483" class="text-gray-800">Purwokerto Timur</option>
                        <option value="-7.4175,109.2233" class="text-gray-800">Purwokerto Barat</option>
                        <option value="-7.3200,109.2200" class="text-gray-800">Baturraden</option>
                        <option value="-7.4667,109.1833" class="text-gray-800">Karanglewas</option>
                        <option value="-7.5333,109.2333" class="text-gray-800">Sokaraja</option>
                    </select>
                </div>
                <div class="flex items-center gap-4 justify-center lg:justify-start">
                    <div id="weather-icon" class="text-5xl md:text-6xl drop-shadow-lg">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                    <div>
                        <h2 class="text-4xl md:text-6xl font-black" id="temp-display">--°C</h2>
                        <p class="text-blue-100 font-medium text-lg" id="weather-desc">Memuat data cuaca...</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full lg:w-auto">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex flex-col items-center justify-center text-center">
                    <i class="fa-solid fa-droplet text-blue-300 mb-2"></i>
                    <p class="text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-1">Kelembapan</p>
                    <p class="font-black text-sm" id="humidity-display">--%</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex flex-col items-center justify-center text-center">
                    <i class="fa-solid fa-wind text-blue-300 mb-2"></i>
                    <p class="text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-1">Angin</p>
                    <p class="font-black text-sm" id="wind-display">-- km/j</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex flex-col items-center justify-center text-center">
                    <i class="fa-solid fa-temperature-half text-blue-300 mb-2"></i>
                    <p class="text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-1">Terasa</p>
                    <p class="font-black text-sm" id="apparent-display">--°C</p>
                </div>
                <div class="bg-white/20 backdrop-blur-md rounded-2xl p-4 border border-white/20 flex flex-col items-center justify-center text-center">
                    <i class="fa-solid fa-map-location-dot text-white mb-2"></i>
                    <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Wilayah</p>
                    <p class="font-black text-sm" id="location-name-display">Purwokerto</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function fetchWeather(lat, lon, name) {
            try {
                const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,wind_speed_10m&timezone=Asia%2FBangkok`);
                const data = await response.json();
                const current = data.current;

                document.getElementById('temp-display').innerText = Math.round(current.temperature_2m) + '°C';
                document.getElementById('humidity-display').innerText = current.relative_humidity_2m + '%';
                document.getElementById('wind-display').innerText = Math.round(current.wind_speed_10m) + ' km/j';
                document.getElementById('apparent-display').innerText = Math.round(current.apparent_temperature) + '°C';
                document.getElementById('location-name-display').innerText = name;

                const code = current.weather_code;
                let desc = "Cerah";
                let icon = '<i class="fa-solid fa-sun text-yellow-300"></i>';

                if (code >= 1 && code <= 3) {
                    desc = "Berawan";
                    icon = '<i class="fa-solid fa-cloud-sun text-blue-200"></i>';
                } else if (code >= 45 && code <= 48) {
                    desc = "Berkabut";
                    icon = '<i class="fa-solid fa-smog text-gray-300"></i>';
                } else if (code >= 51 && code <= 65) {
                    desc = "Hujan";
                    icon = '<i class="fa-solid fa-cloud-showers-heavy text-blue-300"></i>';
                } else if (code >= 80 && code <= 99) {
                    desc = "Badai Petir";
                    icon = '<i class="fa-solid fa-cloud-bolt text-yellow-400"></i>';
                }

                document.getElementById('weather-desc').innerText = desc;
                document.getElementById('weather-icon').innerHTML = icon;

            } catch (error) {
                console.error("Gagal memuat data cuaca:", error);
                document.getElementById('weather-desc').innerText = "Gagal memuat data.";
            }
        }

        function changeLocation() {
            const selector = document.getElementById('location-selector');
            const [lat, lon] = selector.value.split(',');
            const name = selector.options[selector.selectedIndex].text;
            
            document.getElementById('weather-desc').innerText = "Memperbarui...";
            document.getElementById('weather-icon').innerHTML = '<i class="fa-solid fa-spinner fa-spin text-white"></i>';
            
            fetchWeather(lat, lon, name);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Default: Pusat Purwokerto
            fetchWeather(-7.4244, 109.2303, 'Purwokerto');
        });
    </script>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                    <i class="fa-regular fa-user"></i>
                </div>
                @if($total_permohonan > 0)
                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Draft</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mb-1">Total Permohonan Layanan</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $total_permohonan ?? 0 }}</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <i class="fa-regular fa-circle-check"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Pendampingan Selesai</p>
            <h3 class="text-2xl font-bold text-green-600">{{ $pendampingan_selesai ?? '-' }}</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                    <i class="fa-regular fa-file-lines"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Status Verifikasi Data</p>
            <h3 class="text-2xl font-bold text-gray-800">
                {{ $status_verifikasi }}
            </h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Nilai Total Bantuan</p>
            <h3 class="text-2xl font-bold text-gray-800">Rp. {{ number_format($total_bantuan ?? 0, 0, ',', '.') }}</h3>
        </div>
    </div>


    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h3 class="text-lg font-bold text-gray-800 mb-6">Lacak Bantuan Terakhir</h3>

        @if(isset($timeline_activities) && count($timeline_activities) > 0)
            
            <div class="relative">
                <div class="absolute left-3 top-2 bottom-4 w-0.5 bg-gray-200"></div>

                <div class="space-y-8">
                    @foreach($timeline_activities as $activity)
                    <div class="relative flex gap-6">
                        <div class="relative z-10 flex-shrink-0 w-6 h-6 rounded-full {{ $activity['status'] == 'done' ? 'bg-green-500' : ($activity['status'] == 'current' ? 'bg-gray-400' : 'bg-gray-300') }} border-4 border-white shadow-sm mt-1"></div>
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h4 class="font-bold text-gray-900">{{ $activity['title'] }}</h4>
                                @if($activity['status'] == 'current')
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-600 text-xs font-bold rounded">Saat Ini</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mb-1">{{ $activity['date'] }}</p>
                            <p class="text-sm text-gray-600">{{ $activity['description'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        @else

            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-box-open text-2xl"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Belum Ada Aktivitas Bantuan</h4>
                <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                    Anda belum mengajukan permohonan bantuan atau layanan apapun. Silakan lengkapi profil usaha Anda dan ajukan layanan baru.
                </p>
                <a href="{{ route('pembudidaya.ajukan') }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                    + Ajukan Layanan Baru
                </a>
            </div>

        @endif

    </div>

@endsection