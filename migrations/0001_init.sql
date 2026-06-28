CREATE TABLE IF NOT EXISTS registrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  phone TEXT NOT NULL UNIQUE,
  telegram TEXT,
  schedule_code INTEGER NOT NULL CHECK(schedule_code IN (1, 2)),
  receipt_key TEXT,
  status TEXT DEFAULT 'pending'
    CHECK(status IN ('pending', 'approved', 'rejected')),
  admin_note TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  reviewed_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_registrations_status ON registrations(status);
CREATE INDEX IF NOT EXISTS idx_registrations_phone ON registrations(phone);
