<x-layouts.app title="Status Sistem">
    @php
        $overallOk = ($health['status'] ?? 'degraded') === 'ok';
        $databaseCheck = $health['checks']['database'] ?? [];
        $databaseOk = ($databaseCheck['status'] ?? 'degraded') === 'ok';
        $hasCommit = ($version['commit'] ?? null) !== null;
        $payloadPreview = [
            'health' => $health,
            'version' => $version,
        ];
    @endphp

    <section class="w-full max-w-[1040px] mx-auto">
        <div class="rounded-[28px] bg-[#0B1817] border border-[#1E3B37] shadow-[0_30px_80px_-30px_rgba(3,20,18,0.65)] overflow-hidden">

            {{-- Top bar --}}
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 sm:px-8 py-6 border-b border-[#1E3B37] bg-[linear-gradient(135deg,#0F211F,#0B1817)]">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="w-10 h-10 rounded-xl bg-white/5 p-1.5 object-contain">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-[#5FA39A]">RSUD Blambangan</p>
                        <p class="text-lg font-semibold text-white leading-tight">{{ $version['app'] ?? 'CAT Psikiatri' }} <span class="text-[#5FA39A] font-normal">/ Monitoring</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-full pl-3 pr-4 py-2 border {{ $overallOk ? 'border-[#2F8F86]/40 bg-[#2F8F86]/10' : 'border-[#C1594E]/40 bg-[#C1594E]/10' }}">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full rounded-full {{ $overallOk ? 'bg-[#2F8F86]' : 'bg-[#C1594E]' }} opacity-75 animate-ping"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $overallOk ? 'bg-[#3FBBAF]' : 'bg-[#E17569]' }}"></span>
                    </span>
                    <span class="text-sm font-semibold {{ $overallOk ? 'text-[#5FE0D2]' : 'text-[#F0A79E]' }}">
                        {{ $overallOk ? 'Sistem Normal' : 'Ada Gangguan' }}
                    </span>
                </div>
            </div>

            <div class="px-6 sm:px-8 py-8 space-y-8">
                <div>
                    <h1 class="text-2xl sm:text-[28px] font-bold text-white tracking-tight">Status Sistem</h1>
                    <p class="text-[#7FA39C] mt-1.5 text-sm sm:text-base">
                        Ringkasan kondisi aplikasi, koneksi database, dan metadata build untuk verifikasi deploy.
                    </p>
                </div>

                {{-- Metric cards --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    {{-- Health card --}}
                    <div class="rounded-2xl border border-[#1E3B37] bg-[#0F201E] p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#5FA39A]">Health Check</p>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $overallOk ? 'bg-[#2F8F86]/15 text-[#5FE0D2]' : 'bg-[#C1594E]/15 text-[#F0A79E]' }}">
                                {{ strtoupper((string) ($health['status'] ?? 'degraded')) }}
                            </span>
                        </div>
                        <p class="text-[13px] text-[#5C7278] mt-2 font-mono">
                            HTTP {{ $healthHttpStatus }} &middot; {{ $health['timestamp'] ?? '-' }}
                        </p>

                        <div class="mt-4 rounded-xl bg-[#0B1817] border border-[#1E3B37] p-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $databaseOk ? 'bg-[#3FBBAF]' : 'bg-[#E17569]' }}"></span>
                                <p class="text-sm font-semibold text-white">Database</p>
                            </div>
                            <p class="text-sm text-[#8FA8A3] mt-1.5">{{ $databaseCheck['message'] ?? 'Informasi database tidak tersedia.' }}</p>
                            <p class="text-xs text-[#5C7278] mt-2 font-mono">connection: {{ $databaseCheck['connection'] ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Version card --}}
                    <div class="rounded-2xl border border-[#1E3B37] bg-[#0F201E] p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#5FA39A]">Version / Build</p>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $hasCommit ? 'bg-[#2F8F86]/15 text-[#5FE0D2]' : 'bg-white/5 text-[#8FA8A3]' }}">
                                {{ $hasCommit ? 'TERDETEKSI' : 'UNKNOWN' }}
                            </span>
                        </div>

                        <dl class="mt-4 divide-y divide-[#1E3B37] text-sm">
                            @foreach ([
                                'Environment' => $version['environment'] ?? '-',
                                'Version' => $version['version'] ?? '-',
                                'Commit' => $version['commit'] ?? '-',
                                'Commit Short' => $version['commit_short'] ?? '-',
                                'Deployed At' => $version['deployed_at'] ?? '-',
                                'Generated At' => $version['generated_at'] ?? '-',
                            ] as $label => $value)
                                <div class="flex items-center justify-between gap-4 py-2 first:pt-0 last:pb-0">
                                    <dt class="text-[#5C7278]">{{ $label }}</dt>
                                    <dd class="text-[#DCEAE8] font-mono text-[13px] text-right break-all">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                {{-- Endpoints --}}
                <div class="rounded-2xl border border-[#1E3B37] bg-[#0F201E] p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#5FA39A]">Endpoint Monitoring</p>
                    <p class="text-sm text-[#7FA39C] mt-1">Gunakan endpoint berikut untuk integrasi machine-to-machine.</p>
                    <div class="flex flex-wrap gap-2.5 mt-4">
                        <a href="{{ route('monitoring.health') }}" class="inline-flex items-center gap-2 rounded-lg border border-[#2F8F86]/30 bg-[#2F8F86]/10 hover:bg-[#2F8F86]/20 transition-colors px-3.5 py-2 text-[13px] font-mono text-[#5FE0D2]">
                            <span class="text-[#5FA39A]">GET</span> /health
                        </a>
                        <a href="{{ route('monitoring.version') }}" class="inline-flex items-center gap-2 rounded-lg border border-[#2F8F86]/30 bg-[#2F8F86]/10 hover:bg-[#2F8F86]/20 transition-colors px-3.5 py-2 text-[13px] font-mono text-[#5FE0D2]">
                            <span class="text-[#5FA39A]">GET</span> /version
                        </a>
                        <a href="{{ route('monitoring.build-info') }}" class="inline-flex items-center gap-2 rounded-lg border border-[#2F8F86]/30 bg-[#2F8F86]/10 hover:bg-[#2F8F86]/20 transition-colors px-3.5 py-2 text-[13px] font-mono text-[#5FE0D2]">
                            <span class="text-[#5FA39A]">GET</span> /build-info
                        </a>
                    </div>
                </div>

                {{-- Payload preview --}}
                <div class="rounded-2xl border border-[#1E3B37] bg-[#0F201E] p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#5FA39A]">Payload Preview</p>
                    <p class="text-sm text-[#7FA39C] mt-1">Snapshot payload untuk verifikasi cepat dari browser.</p>
                    <pre class="mt-4 rounded-xl bg-[#0B1817] border border-[#1E3B37] p-4 text-[12.5px] leading-relaxed text-[#9FD6CD] font-mono overflow-x-auto">{{ json_encode($payloadPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
