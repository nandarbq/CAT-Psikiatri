<x-layouts.admin
    title="Pengaturan"
    heading="Pengaturan"
    subtitle="Kelola profil akun Anda dan lokasi RSUD untuk validasi akses ujian onsite."
>
    <div class="settings-tabs" role="tablist" aria-label="Tab Pengaturan">
        <a
            href="{{ route('admin.settings.index', ['tab' => 'profile']) }}"
            class="settings-tab {{ $activeTab === 'profile' ? 'is-active' : '' }}"
            role="tab"
            aria-selected="{{ $activeTab === 'profile' ? 'true' : 'false' }}"
        >
            Profil Saya
        </a>
        <a
            href="{{ route('admin.settings.index', ['tab' => 'location']) }}"
            class="settings-tab {{ $activeTab === 'location' ? 'is-active' : '' }}"
            role="tab"
            aria-selected="{{ $activeTab === 'location' ? 'true' : 'false' }}"
        >
            Lokasi RSUD
        </a>
    </div>

    @if ($activeTab === 'profile')
        <section class="admin-form-card">
            <form
                method="POST"
                action="{{ route('admin.settings.profile.update') }}"
                class="form-grid"
                data-confirm-title="Simpan Profil"
                data-confirm-message="Simpan perubahan profil sekarang?"
                data-confirm-button-text="Ya, simpan"
            >
                @csrf
                @method('PUT')

                <label class="field">
                    <span>Nama Lengkap</span>
                    <input type="text" name="name" value="{{ old('name', $currentUser->name) }}" required class="input" autocomplete="name">
                </label>
                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" value="{{ old('username', $currentUser->username) }}" required class="input" autocomplete="username">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $currentUser->email) }}" required class="input" autocomplete="email">
                </label>
                <label class="field">
                    <span>Password Baru</span>
                    <input type="password" name="password" class="input" autocomplete="new-password">
                    <small class="field-help">Kosongkan jika tidak ingin mengubah password.</small>
                </label>
                <label class="field">
                    <span>Konfirmasi Password Baru</span>
                    <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
                </label>

                <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </form>
        </section>
    @else
        <section class="admin-form-card location-picker-card">
            <form method="POST" action="{{ route('admin.settings.location.update') }}" class="location-picker-grid">
                @csrf
                @method('PUT')

                <div class="location-picker-map-wrap">
                    <div id="osm-location-map" class="location-picker-map" aria-label="Peta OpenStreetMap"></div>
                </div>

                <div class="location-picker-controls">
                    <label class="field">
                        <span>Latitude Pusat RSUD</span>
                        <input id="lp-lat" class="input" type="number" step="0.0000001" name="center_latitude" value="{{ old('center_latitude', $locationSetting->center_latitude) }}" required>
                    </label>

                    <label class="field">
                        <span>Longitude Pusat RSUD</span>
                        <input id="lp-lng" class="input" type="number" step="0.0000001" name="center_longitude" value="{{ old('center_longitude', $locationSetting->center_longitude) }}" required>
                    </label>

                    <label class="field">
                        <span>Radius Akses (meter)</span>
                        <input id="lp-radius" class="input" type="number" min="10" max="50000" name="allowed_radius_meters" value="{{ old('allowed_radius_meters', $locationSetting->allowed_radius_meters) }}" required>
                    </label>

                    <p class="subtitle-tight">
                        Klik peta untuk memindahkan titik RSUD. Perubahan di sini berlaku ke semua sesi dengan mode akses "Dalam Radius RSUD".
                    </p>

                    <div class="location-picker-actions">
                        <button id="lp-my-location" type="button" class="btn btn-ghost">Gunakan Lokasi Saya</button>
                        <button type="submit" class="btn btn-primary">Simpan Lokasi RSUD</button>
                    </div>
                </div>
            </form>
        </section>

        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        >
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            (() => {
                const latInput = document.getElementById('lp-lat');
                const lngInput = document.getElementById('lp-lng');
                const radiusInput = document.getElementById('lp-radius');
                const myLocationButton = document.getElementById('lp-my-location');

                if (!latInput || !lngInput || !radiusInput || !myLocationButton || typeof window.L === 'undefined') {
                    return;
                }

                const fallbackLat = -6.2015;
                const fallbackLng = 106.8166667;
                const parsedLat = Number.parseFloat(latInput.value);
                const parsedLng = Number.parseFloat(lngInput.value);
                const startLat = Number.isFinite(parsedLat) ? parsedLat : fallbackLat;
                const startLng = Number.isFinite(parsedLng) ? parsedLng : fallbackLng;
                const parsedRadius = Number.parseInt(radiusInput.value, 10);
                const startRadius = Number.isFinite(parsedRadius) && parsedRadius >= 10 ? parsedRadius : 500;

                radiusInput.value = String(startRadius);
                latInput.value = Number(startLat).toFixed(7);
                lngInput.value = Number(startLng).toFixed(7);

                const map = L.map('osm-location-map').setView([startLat, startLng], 16);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                }).addTo(map);

                const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
                const circle = L.circle([startLat, startLng], {
                    radius: startRadius,
                    color: '#3f5b73',
                    fillColor: '#5b8299',
                    fillOpacity: 0.18,
                    weight: 2,
                }).addTo(map);

                const syncFromPoint = (lat, lng, shouldFit = false) => {
                    latInput.value = Number(lat).toFixed(7);
                    lngInput.value = Number(lng).toFixed(7);

                    const radius = Number.parseInt(radiusInput.value, 10);
                    const safeRadius = Number.isFinite(radius) && radius >= 10 ? radius : 500;
                    radiusInput.value = String(safeRadius);

                    marker.setLatLng([lat, lng]);
                    circle.setLatLng([lat, lng]);
                    circle.setRadius(safeRadius);

                    if (shouldFit) {
                        map.fitBounds(circle.getBounds(), { padding: [24, 24] });
                    }
                };

                map.on('click', (event) => {
                    syncFromPoint(event.latlng.lat, event.latlng.lng, true);
                });

                marker.on('dragend', () => {
                    const point = marker.getLatLng();
                    syncFromPoint(point.lat, point.lng, false);
                });

                radiusInput.addEventListener('input', () => {
                    const radius = Number.parseInt(radiusInput.value, 10);
                    const safeRadius = Number.isFinite(radius) && radius >= 10 ? radius : 10;
                    radiusInput.value = String(safeRadius);
                    circle.setRadius(safeRadius);
                });

                myLocationButton.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        window.AppDialog.alert({
                            title: 'Fitur Tidak Didukung',
                            text: 'Browser tidak mendukung geolokasi.',
                            icon: 'error',
                        });
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            map.setView([lat, lng], 17);
                            syncFromPoint(lat, lng, true);
                        },
                        () => {
                            window.AppDialog.alert({
                                title: 'Lokasi Gagal Diambil',
                                text: 'Tidak bisa mengambil lokasi perangkat. Pastikan izin lokasi diaktifkan.',
                                icon: 'error',
                            });
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 30000,
                        }
                    );
                });
            })();
        </script>
    @endif
</x-layouts.admin>
