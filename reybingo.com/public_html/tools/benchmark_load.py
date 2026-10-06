#!/usr/bin/env python3
"""
Benchmark y Prueba de Carga Progresiva y Controlada para Rey Bingo
==================================================================
Ejecuta pruebas concurrentes por niveles (10, 25, 50, 100, 150, 200) midiendo:
- Respuestas HTTP (200, 304, 400, 500, 502, 504)
- Tiempos de respuesta: Avg, P95, P99, Min, Max
- Throughput (req/s)
- Proteccion activa: se detiene automaticamente si la tasa de error supera el 5%

Uso:
  python benchmark_load.py --url https://bingo.reybingo.com --endpoint /assets/js/playing.js
  python benchmark_load.py --url https://bingo.reybingo.com --endpoint /cron/ping
"""

import sys
import time
import argparse
import urllib.request
import urllib.error
import concurrent.futures
from statistics import mean

def percentile(data, p):
    if not data:
        return 0
    k = (len(data) - 1) * (p / 100.0)
    f = int(k)
    c = min(f + 1, len(data) - 1)
    d0 = data[f] * (c - k)
    d1 = data[c] * (k - f)
    return round(d0 + d1, 2)

def fetch_worker(url, timeout=10.0):
    start = time.perf_counter()
    status = 0
    try:
        req = urllib.request.Request(
            url, 
            headers={'User-Agent': 'ReyBingo-LoadTester/1.0'}
        )
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            status = resp.status
    except urllib.error.HTTPError as e:
        status = e.code
    except urllib.error.URLError as e:
        if 'timed out' in str(e).lower():
            status = 504
        else:
            status = 502
    except Exception:
        status = 500
    elapsed_ms = (time.perf_counter() - start) * 1000.0
    return status, elapsed_ms

def run_level(base_url, endpoint, concurrency, requests_per_worker=5, timeout=10.0):
    total_requests = concurrency * requests_per_worker
    target_url = base_url.rstrip('/') + endpoint

    print(f"\n[NIVEL] {concurrency} Conexiones Concurrentes ({total_requests} requests totales)...")
    
    statuses = {200: 0, 304: 0, 400: 0, 500: 0, 502: 0, 504: 0, 'otros': 0}
    latencies = []

    start_wall = time.perf_counter()

    with concurrent.futures.ThreadPoolExecutor(max_workers=concurrency) as executor:
        futures = [executor.submit(fetch_worker, target_url, timeout) for _ in range(total_requests)]
        for f in concurrent.futures.as_completed(futures):
            st, lat = f.result()
            latencies.append(lat)
            if st in statuses:
                statuses[st] += 1
            else:
                statuses['otros'] += 1

    total_time = time.perf_counter() - start_wall
    latencies.sort()

    rps = round(total_requests / total_time, 1) if total_time > 0 else 0
    avg_lat = round(mean(latencies), 1) if latencies else 0
    p95 = percentile(latencies, 95)
    p99 = percentile(latencies, 99)
    min_lat = round(latencies[0], 1) if latencies else 0
    max_lat = round(latencies[-1], 1) if latencies else 0

    errors = statuses[500] + statuses[502] + statuses[504]
    error_rate = (errors / total_requests) * 100 if total_requests > 0 else 0

    print(f"  -> Resultado: HTTP 200={statuses[200]} | 500={statuses[500]} | 502={statuses[502]} | 504={statuses[504]}")
    print(f"  -> Latencias: Promedio={avg_lat}ms | P95={p95}ms | P99={p99}ms | Min={min_lat}ms | Max={max_lat}ms")
    print(f"  -> Throughput: {rps} req/s | Tasa Errores: {error_rate:.1f}%")

    return {
        'concurrency': concurrency,
        'total': total_requests,
        'statuses': statuses,
        'avg': avg_lat,
        'p95': p95,
        'p99': p99,
        'rps': rps,
        'error_rate': error_rate
    }

def main():
    parser = argparse.ArgumentParser(description="Prueba de carga progresiva para Rey Bingo")
    parser.add_argument('--url', default='https://bingo.reybingo.com', help='URL base')
    parser.add_argument('--endpoint', default='/assets/js/playing.js', help='Ruta a consultar')
    parser.add_argument('--levels', default='10,25,50,100,150,200', help='Niveles de concurrencia separados por coma')
    args = parser.parse_args()

    levels = [int(x.strip()) for x in args.levels.split(',') if x.strip().isdigit()]

    print("=============================================================")
    print(" INICIO DE PRUEBA DE CARGA CONTROLADA - REY BINGO")
    print(f" Target: {args.url}{args.endpoint}")
    print(f" Niveles: {levels}")
    print("=============================================================")

    history = []
    for lvl in levels:
        res = run_level(args.url, args.endpoint, lvl)
        history.append(res)
        if res['error_rate'] > 5.0:
            print(f"\n[ALERTA DE SEGURIDAD] Tasa de error ({res['error_rate']:.1f}%) supera el 5%. Deteniendo prueba para proteger el servidor.")
            break
        time.sleep(1.0) # Pausa de enfriamiento entre niveles

    print("\n=============================================================")
    print(" RESUMEN FINAL DE PRUEBA DE CARGA")
    print("=============================================================")
    print(f"{'Concurr':<8} | {'Total':<6} | {'200':<5} | {'500':<5} | {'502/504':<8} | {'Avg(ms)':<8} | {'P95(ms)':<8} | {'RPS':<6}")
    print("-" * 65)
    for h in history:
        s50x = h['statuses'][502] + h['statuses'][504]
        print(f"{h['concurrency']:<8} | {h['total']:<6} | {h['statuses'][200]:<5} | {h['statuses'][500]:<5} | {s50x:<8} | {h['avg']:<8} | {h['p95']:<8} | {h['rps']:<6}")
    print("=============================================================\n")

if __name__ == '__main__':
    main()
