"""
Jembatan Arduino (USB/Serial) <-> Laravel API  (v2, dengan diagnosa)

Install sekali:  pip install pyserial requests
Jalankan:        python bridge.py
"""
import re
import sys
import time
from pathlib import Path

import requests
import serial
from serial.tools import list_ports

# ====== SESUAIKAN ======
PORT = "AUTO"      # "AUTO" = cari Arduino otomatis; atau isi manual, mis. "COM3"
BAUD = 9600
API_URL = "http://127.0.0.1:8000/api/gate/check"
GATE_KEY = ""      # kosong = otomatis dibaca dari ..\.env (baris GATE_KEY=...)
# =======================


def log(*a):
    print(*a, flush=True)


def baca_gate_key():
    if GATE_KEY.strip():
        return GATE_KEY.strip()
    env = Path(__file__).resolve().parent.parent / ".env"
    if env.exists():
        for baris in env.read_text(encoding="utf-8-sig", errors="ignore").splitlines():
            m = re.match(r"\s*GATE_KEY\s*=\s*(.+?)\s*$", baris)
            if m:
                return m.group(1).strip("\"'")
    sys.exit("GATE_KEY tidak ditemukan. Isi di bridge.py atau pastikan ..\\.env berisi GATE_KEY=...")


def tentukan_port():
    ports = list(list_ports.comports())
    log("Port serial yang terdeteksi:")
    if not ports:
        log("  (tidak ada)")
    for p in ports:
        log(f"  {p.device}: {p.description}")

    if PORT.upper() != "AUTO":
        return PORT

    kata = ("arduino", "ch340", "ch341", "usb-serial", "usb serial", "ftdi", "cp210")
    for p in ports:
        teks = f"{p.description} {p.manufacturer}".lower()
        if any(k in teks for k in kata):
            return p.device
    sys.exit("Arduino tidak terdeteksi otomatis. Cabut-colok USB, atau isi PORT manual di bridge.py.")


def main():
    key = baca_gate_key()
    headers = {"X-Gate-Key": key, "Accept": "application/json"}
    port = tentukan_port()

    try:
        ser = serial.Serial(port, BAUD, timeout=1)
    except serial.SerialException as e:
        sys.exit(f"Tidak bisa membuka {port}: {e}\nTutup Serial Monitor Arduino IDE, lalu coba lagi.")

    time.sleep(2)  # Arduino biasanya reset saat port dibuka
    log(f"\nTerhubung ke {port}. Server: {API_URL}")
    log("Menunggu kartu... (Ctrl+C untuk berhenti)\n")

    terakhir = time.time()
    while True:
        line = ser.readline().decode(errors="ignore").strip()

        if not line:
            if time.time() - terakhir > 15:
                log("(15 detik belum ada data dari Arduino. Salah port? Tap kartu untuk mengetes.)")
                terakhir = time.time()
            continue

        terakhir = time.time()

        if not line.startswith("UID:"):
            log("Arduino:", line)
            continue

        uid = line[4:].strip()
        log(f"Kartu terbaca: {uid}")

        try:
            r = requests.post(API_URL, json={"uid": uid}, headers=headers, timeout=3)
        except requests.RequestException as e:
            log(f"  -> ERROR tidak bisa menghubungi server: {e}")
            ser.write(b"DENIED\n")
            continue

        if r.status_code == 401:
            log("  -> ERROR 401: GATE_KEY salah / tidak cocok dengan .env")
            ser.write(b"DENIED\n")
            continue
        if r.status_code != 200:
            log(f"  -> ERROR HTTP {r.status_code}: {r.text[:200]}")
            ser.write(b"DENIED\n")
            continue

        d = r.json()
        if d.get("status") == "granted":
            log(f"  -> DITERIMA ({d.get('name')})")
            ser.write(f"GRANTED|{d.get('name')}|{d.get('position') or ''}\n".encode())
        else:
            log("  -> DITOLAK")
            ser.write(b"DENIED\n")


if __name__ == "__main__":
    main()