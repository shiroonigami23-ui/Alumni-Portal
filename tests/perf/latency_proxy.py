#!/usr/bin/env python3
"""
Adds network latency between the app and PostgreSQL, so local benchmarks behave like production
(where the database is on another machine). Every byte in each direction waits DELAY ms.

    python3 tests/perf/latency_proxy.py 0.5        # listen on :6543, forward to :5432, 0.5 ms each way
    DB_PORT=6543 php -S 127.0.0.1:8099 router.php  # point the app at the proxy
"""
import asyncio, sys
DELAY = float(sys.argv[1]) / 1000.0   # one-way delay in seconds
async def pipe(r, w):
    try:
        while True:
            data = await r.read(65536)
            if not data: break
            await asyncio.sleep(DELAY)
            w.write(data); await w.drain()
    except Exception: pass
    finally:
        try: w.close()
        except Exception: pass
async def handle(cr, cw):
    sr, sw = await asyncio.open_connection('127.0.0.1', 5432)
    await asyncio.gather(pipe(cr, sw), pipe(sr, cw))
async def main():
    srv = await asyncio.start_server(handle, '127.0.0.1', 6543)
    async with srv: await srv.serve_forever()
asyncio.run(main())
