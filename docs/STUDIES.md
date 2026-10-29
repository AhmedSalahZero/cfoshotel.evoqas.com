# Studies (Step 07)

The study itself: list, create, edit, copy, delete and share.

## Who sees and changes what
| Person | Sees | Changes | Shares / deletes |
|---|---|---|---|
| Owner (the creator) | own study | yes | yes |
| Company administrator | every study of the company | yes | yes |
| Colleague, shared "can edit" | that study | yes | no |
| Colleague, shared "view only" | that study (form locked) | no | no |
| Other company | nothing (404) | no | no |

A company whose subscription ended is read-only: everyone can look, nobody can change.
The rule lives in one place: `App\Models\Concerns\HospitalitySectorAccess` and `App\Policies\HospitalitySectorPolicy`.

## Dates
The browser sends only the first month and the length. The server works out the end month, the operation start and the
20-calendar-year month list (`App\Support\StudyCalendar`). Checked against all five real studies of the old project.
Months are kept in the old format (`MM/01/YYYY`) so the calculations of the next steps read them unchanged.

## Revenue streams
Rooms, food and beverage are always part of a study. Casino, meetings and other revenue have an on/off switch;
switching one off deletes its rows. Each stream is either ONE total or rows BY TYPE (each type once, from the company's own list,
never the "Total ..." row).

## Copy
Everything hanging on the study is copied (streams, channels, expenses, fees, furniture, acquisitions, loans). Loans are
re-pointed at the copied parents. The copy belongs to the person who copied it and is not shared with anyone.
A test fails if a new table with `hospitality_sector_id` is added without teaching the copy about it.

## Decisions to review
- Casino, meetings and other revenue start switched off in a new study.
- Any currency can be the main currency.
- Operation starting before development ends is allowed (screen warning only).
- Compare between two studies and the result links per study come with the dashboard step.
