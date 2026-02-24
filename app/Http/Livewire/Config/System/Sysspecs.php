<?php

namespace App\Http\Livewire\Config\System;

use Livewire\Component;

class Sysspecs extends Component
{
    public int $maxPoints = 30; // limite de pontos historicos mantidos para os graficos

    // Discos
    public array $disks = [];

    // Memória
    public int $memTotal = 0;
    public int $memUsed = 0;
    public int $memFree = 0;
    public int $memCached = 0;
    public int $memBuffers = 0;
    public int $memSReclaim = 0;
    public int $swapTotal = 0;
    public int $swapUsed = 0;
    public int $swapFree = 0;

    // CPU
    public float $cpuUsage = 0.0;
    public int $cpuCores = 1;
    public ?int $cpuTempC = null;
    public array $load = ['1min'=>0.0,'5min'=>0.0,'15min'=>0.0];

    // Uptime
    public string $uptimeHuman = '—';

    // Top processos
    public array $topProcs = [];

    // PHP atual
    public float $phpMemUsed = 0.0;
    public float $phpMemPeak = 0.0;

    // Series para graficos realtime
    public array $chartLabels = [];
    public array $cpuSeries = [];
    public array $memorySeries = [];
    public array $swapSeries = [];
    public array $loadSeries = [];

    // SO
    public string $osFamily = 'linux';
    private string $defaultPath = '/';

    // Estado anterior p/ CPU%
    private ?int $prevCpuTotal = null;
    private ?int $prevCpuIdle  = null;

    public function mount()
    {
        $this->detectOS();
        $this->primeCpuStat();
        $this->updateSystemStatus();
    }

    public function render()
    {
        return view('livewire.config.system.sysspecs');
    }

    public function updateSystemStatus()
    {
        $this->updateCpu();
        $this->updateLoadAndUptime();
        $this->updateMemory();
        $this->updateSwap();
        $this->updateDisks();
        $this->updateTopProcs();
        $this->updatePhpProcess();
        $this->updateCpuTempIfAvailable();
        $this->updateRealtimeSeries();
        $this->dispatchRealtimeCharts();
    }

    /* ===================== Sistema ===================== */

    private function detectOS(): void
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->osFamily = $isWin ? 'windows' : 'linux';
        $this->defaultPath = $isWin ? 'C:\\' : '/';
    }

    private function primeCpuStat(): void
    {
        if ($this->osFamily === 'linux') {
            [$t, $i] = $this->readProcStatTotals();
            $this->prevCpuTotal = $t;
            $this->prevCpuIdle  = $i;
        }
    }

    private function updateCpu(): void
    {
        if ($this->osFamily === 'linux') {
            $cores = (int) @shell_exec('nproc 2>/dev/null');
            $this->cpuCores = $cores > 0 ? $cores : max(1, (int) @shell_exec("grep -c ^processor /proc/cpuinfo 2>/dev/null"));

            [$t, $i] = $this->readProcStatTotals();
            if ($this->prevCpuTotal !== null && $this->prevCpuIdle !== null) {
                $dt = max(1, $t - $this->prevCpuTotal);
                $di = max(0, $i - $this->prevCpuIdle);
                $this->cpuUsage = round((1 - ($di / $dt)) * 100, 1);
            }
            $this->prevCpuTotal = $t;
            $this->prevCpuIdle  = $i;
        } else {
            $this->cpuCores = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 1);
        }
    }

    private function readProcStatTotals(): array
    {
        $stat = @file_get_contents('/proc/stat');
        if ($stat === false) return [0,0];
        if (preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)?\s+(\d+)?\s+(\d+)?/m', $stat, $m)) {
            $user=(int)$m[1]; $nice=(int)$m[2]; $sys=(int)$m[3]; $idle=(int)$m[4];
            $iow = isset($m[5]) ? (int)$m[5] : 0;
            $irq = isset($m[6]) ? (int)$m[6] : 0;
            $sft = isset($m[7]) ? (int)$m[7] : 0;
            $total = $user + $nice + $sys + $idle + $iow + $irq + $sft;
            return [$total, $idle + $iow];
        }
        return [0,0];
    }

    private function updateLoadAndUptime(): void
    {
        if ($this->osFamily === 'linux') {
            $avg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0,0,0];
            $this->load = [
                '1min'  => isset($avg[0]) ? round((float)$avg[0], 2) : 0.0,
                '5min'  => isset($avg[1]) ? round((float)$avg[1], 2) : 0.0,
                '15min' => isset($avg[2]) ? round((float)$avg[2], 2) : 0.0,
            ];
            $u = @file_get_contents('/proc/uptime');
            if ($u !== false) {
                $secs = (int)floatval(explode(' ', trim($u))[0]);
                $this->uptimeHuman = $this->humanSeconds($secs);
            }
        }
    }

    private function updateMemory(): void
    {
        if ($this->osFamily === 'linux') {
            $meminfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $map=[];
            if ($meminfo) {
                foreach ($meminfo as $line) {
                    if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) $map[$m[1]]=(int)$m[2];
                }
            }
            $total = $map['MemTotal'] ?? 0;
            $free  = $map['MemFree'] ?? 0;
            $buf   = $map['Buffers'] ?? 0;
            $cached= $map['Cached'] ?? 0;
            $srec  = $map['SReclaimable'] ?? 0;
            $used  = max(0, $total - ($free + $buf + $cached + $srec));

            $this->memTotal   = (int) round($total / 1024);
            $this->memUsed    = (int) round($used  / 1024);
            $this->memFree    = (int) round(($total - $used) / 1024);
            $this->memBuffers = (int) round($buf   / 1024);
            $this->memCached  = (int) round($cached/ 1024);
            $this->memSReclaim= (int) round($srec  / 1024);
        }
    }

    private function updateSwap(): void
    {
        if ($this->osFamily === 'linux') {
            $meminfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $map=[];
            if ($meminfo) foreach ($meminfo as $line) if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) $map[$m[1]]=(int)$m[2];
            $st=$map['SwapTotal']??0; $sf=$map['SwapFree']??0; $su=max(0,$st-$sf);
            $this->swapTotal=(int)round($st/1024); $this->swapFree=(int)round($sf/1024); $this->swapUsed=(int)round($su/1024);
        }
    }

    private function updateDisks(): void
    {
        $this->disks = [];
        if ($this->osFamily === 'linux') {
            $out = @shell_exec('df -P -B1 2>/dev/null');
            if ($out) {
                $lines = preg_split('/\r?\n/', trim($out));
                array_shift($lines);
                foreach ($lines as $ln) {
                    $p = preg_split('/\s+/', trim($ln));
                    if (count($p) < 6) continue;
                    [$fs,$blocks,$used,$avail,$pcent,$mount] = array_slice($p,0,6);
                    if (strpos($fs,'tmpfs')===0 || strpos($fs,'devtmpfs')===0) continue;
                    $total = (int)$blocks; $u=(int)$used; $f=(int)$avail;
                    $pct = $total>0 ? ($u/$total)*100 : 0;
                    $this->disks[] = [
                        'fs'=>$fs, 'mount'=>$mount,
                        'total'=>$this->humanBytes($total),
                        'used'=>$this->humanBytes($u),
                        'free'=>$this->humanBytes($f),
                        'used_pct'=>round($pct,1),
                    ];
                }
                return;
            }
        }
        // Fallback único
        $free  = @disk_free_space($this->defaultPath) ?: 0;
        $total = @disk_total_space($this->defaultPath) ?: 0;
        $used  = max(0,$total-$free);
        $pct   = $total>0 ? ($used/$total)*100 : 0;
        $this->disks[] = [
            'fs'=>'—','mount'=>$this->defaultPath,
            'total'=>$this->humanBytes($total),
            'used'=>$this->humanBytes($used),
            'free'=>$this->humanBytes($free),
            'used_pct'=>round($pct,1),
        ];
    }

    private function updateTopProcs(): void
    {
        $this->topProcs = [];
        if ($this->osFamily === 'linux') {
            $out = @shell_exec("ps -eo pid,comm,%cpu,%mem --no-headers --sort=-%cpu | head -n 5");
            if ($out) {
                foreach (preg_split('/\r?\n/', trim($out)) as $ln) {
                    if (!$ln) continue;
                    $parts = preg_split('/\s+/', trim($ln));
                    if (count($parts)<4) continue;
                    $pid = array_shift($parts);
                    $mem = array_pop($parts);
                    $cpu = array_pop($parts);
                    $cmd = implode(' ', $parts);
                    $this->topProcs[] = ['pid'=>(int)$pid,'cmd'=>$cmd,'cpu'=>(float)$cpu,'mem'=>(float)$mem];
                }
            }
        }
    }

    private function updatePhpProcess(): void
    {
        $this->phpMemUsed = round(memory_get_usage() / 1024 / 1024, 2);
        $this->phpMemPeak = round(memory_get_peak_usage() / 1024 / 1024, 2);
    }

    private function updateCpuTempIfAvailable(): void
    {
        if ($this->osFamily !== 'linux') { $this->cpuTempC = null; return; }
        $candidates = ['/sys/class/thermal/thermal_zone0/temp','/sys/class/hwmon/hwmon0/temp1_input'];
        foreach ($candidates as $p) {
            if (is_readable($p)) {
                $raw = trim((string) @file_get_contents($p));
                if ($raw!=='') { $v=(int)$raw; $this->cpuTempC = $v>1000 ? (int)round($v/1000) : $v; return; }
            }
        }
        $this->cpuTempC = null;
    }

    /* ===================== Utils ===================== */

    private function humanBytes(int|float $bytes): string
    {
        $units=['B','KB','MB','GB','TB','PB']; $i=0; $v=(float)$bytes;
        while ($v>=1024 && $i<count($units)-1) { $v/=1024; $i++; }
        return sprintf('%.2f %s',$v,$units[$i]);
    }

    private function humanSeconds(int $sec): string
    {
        $d=intdiv($sec,86400); $sec%=86400;
        $h=intdiv($sec,3600);  $sec%=3600;
        $m=intdiv($sec,60);
        if($d>0) return "{$d}d {$h}h {$m}m";
        if($h>0) return "{$h}h {$m}m";
        return "{$m}m";
    }

    public function barClass(float $pct): string
    {
        return $pct >= 90 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success');
    }

    public function badgeClass(float $pct): string
    {
        return $pct >= 90 ? 'text-bg-danger' : ($pct >= 75 ? 'text-bg-warning' : 'text-bg-success');
    }

    public function loadBadge(float $load): string
    {
        $ratio = $this->cpuCores > 0 ? ($load / $this->cpuCores) * 100 : 0;
        return $this->badgeClass($ratio);
    }

    private function updateRealtimeSeries(): void
    {
        $this->pushSeriesValue($this->chartLabels, now()->format('H:i:s'));
        $this->pushSeriesValue($this->cpuSeries, round($this->cpuUsage, 1));

        $memPct = $this->memTotal > 0 ? round(($this->memUsed / max(1, $this->memTotal)) * 100, 1) : 0;
        $swapPct = $this->swapTotal > 0 ? round(($this->swapUsed / max(1, $this->swapTotal)) * 100, 1) : 0;

        $this->pushSeriesValue($this->memorySeries, $memPct);
        $this->pushSeriesValue($this->swapSeries, $swapPct);
        $this->pushSeriesValue($this->loadSeries, round((float) ($this->load['1min'] ?? 0), 2));
    }

    private function pushSeriesValue(array &$series, float|int|string $value): void
    {
        $series[] = $value;
        if (count($series) > $this->maxPoints) {
            array_shift($series);
        }
    }

    private function dispatchRealtimeCharts(): void
    {
        $this->dispatchBrowserEvent('config-sysspecs-realtime', [
            'labels' => $this->chartLabels,
            'cpu' => $this->cpuSeries,
            'memory' => $this->memorySeries,
            'swap' => $this->swapSeries,
            'load' => $this->loadSeries,
            'maxLoadScale' => max(1, round((float) ($this->cpuCores * 1.5), 1)),
            'pointLimit' => $this->maxPoints,
        ]);
    }
}
