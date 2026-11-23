# Attendance log imports

HR can import ZKTeco `.dat` or `.txt` files from **HRIS → Attendance → Import Attendance Logs**. Files contain six tab-separated columns without a header:

1. Machine user ID
2. Local punch time (`YYYY-MM-DD HH:MM:SS`)
3. Device ID
4. Punch state
5. Verification mode
6. Work code

The import screen previews row counts, date range, distinct and unlinked machine IDs, malformed rows, and rows that will be skipped. Confirming creates an audited import batch. Malformed lines are reported without discarding valid lines. Re-uploading is allowed; rows with an existing `(machine_user_id, punched_at, device_id)` key are skipped while other punches are stored.

Raw state, verification mode, work code, device ID, and the original local timestamp are retained. The derived slot and review flags are stored separately. Punch-state slots are `0 = AM_IN`, `2 = LUNCH_OUT`, `3 = PM_IN`, `1 = PM_OUT`, `4 = OT_IN`, and `5 = OT_OUT`. A `verify_mode` of `0` is flagged for PIN review; it is not an import error. Times are not converted to UTC.

The **Daily Punches** view groups punches by linked employee or unlinked machine ID and date. It shows missing regular slots, suspected wrong-button punches, duplicate punches, and PIN verification for HR review. A missing punch is not automatically treated as an absence or undertime. Approved leave, travel orders, holidays, and configured non-working schedules are shown as context. The export is an Excel-compatible XML workbook with a daily summary sheet and a raw-punch sheet.

Attendance settings can be adjusted through environment configuration:

| Variable                              | Default       | Meaning                                                    |
| ------------------------------------- | ------------- | ---------------------------------------------------------- |
| `ATTENDANCE_TIMEZONE`                 | `Asia/Manila` | Timezone label for device-local punch times                |
| `ATTENDANCE_DUPLICATE_WINDOW_SECONDS` | `120`         | Maximum interval used to flag a later punch as a duplicate |
| `ATTENDANCE_AM_IN_END`                | `12:00`       | End of the AM_IN suggestion window                         |
| `ATTENDANCE_LUNCH_OUT_END`            | `12:45`       | End of the LUNCH_OUT suggestion window                     |
| `ATTENDANCE_PM_IN_END`                | `16:00`       | End of the PM_IN suggestion window                         |

Window-based suggestions never replace the device's punch state. The current schedule schema does not define a half-day period; half-day handling should be added when that schedule data is modeled.
