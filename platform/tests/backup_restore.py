#!/usr/bin/env python3
"""Offline encrypted JSON backup+restore round-trip; no secrets or payment provider calls."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[1]

with tempfile.TemporaryDirectory(prefix='brendigo-pay-json-backup-') as td:
    base = Path(td)
    test_project = base / 'platform'
    (test_project / 'bin').mkdir(parents=True)
    (test_project / 'public').mkdir()
    data = test_project / 'storage' / 'data'
    (data / 'users').mkdir(parents=True)
    (data / 'sessions').mkdir()
    (data / 'users' / 'account.json').write_text('{"id":"account","name":"Tester"}\n')
    (data / 'sessions' / 'expired.json').write_text('{"secret":"session"}')
    (test_project / 'config.php').write_text(
        "<?php return ['data_dir'=>" + repr(str(data)).replace('\\', '/') + "];\n"
    )
    for filename in ('backup.php', 'restore.php'):
        shutil.copy(ROOT / 'bin' / filename, test_project / 'bin' / filename)
    archive = base / 'encrypted-backup.bpenc'
    env = os.environ | {'BP_BACKUP_PASSPHRASE': 'sample-test-passphrase-not-for-production'}
    subprocess.run(['php', 'bin/backup.php', str(archive)], cwd=test_project, env=env,
                   check=True, capture_output=True, text=True)
    raw = archive.read_bytes()
    assert raw.startswith(b'BPJSON03') and b'account' not in raw and b'Tester' not in raw
    assert archive.stat().st_mode & 0o777 == 0o600
    (data / 'users' / 'account.json').unlink()
    (data / 'sessions' / 'expired.json').unlink()
    wrong = subprocess.run(['php', 'bin/restore.php', str(archive)], cwd=test_project,
                           env=os.environ | {'BP_BACKUP_PASSPHRASE': 'another-password-123456789'},
                           capture_output=True, text=True)
    assert wrong.returncode != 0
    subprocess.run(['php', 'bin/restore.php', str(archive)], cwd=test_project,
                   env=env, check=True, capture_output=True, text=True)
    assert 'Tester' in (data / 'users' / 'account.json').read_text()
    assert not list((data / 'sessions').glob('*.json'))
    no_overwrite = subprocess.run(['php', 'bin/restore.php', str(archive)], cwd=test_project,
                                  env=env, capture_output=True, text=True)
    assert no_overwrite.returncode != 0
    print('PASS: encrypted backup and restore round-trip, wrong password and overwrite rejected')
