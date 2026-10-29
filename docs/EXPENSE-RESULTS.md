# Expense results page

Screen: `/studies/{study}/expenses-results` (Results group, next to the revenue results). Read only; anyone who may view the study can open it.

## What it shows (one column for every calendar year of the study, then a total)
1. **Direct expenses** — for each department in use (rooms, F&B, gaming, meetings, other revenue): variable % of revenue, per night, per guest, disposable, manpower, and the group total.
2. **Undistributed operating expenses** — general, sales & marketing, property, energy: variable %, fixed monthly, manpower, and the group total.
3. **Management fees** — the base fee.
4. **Start-up cost and pre-operating expenses** — as they are paid, month by month, added up per year (the years before the opening show here).
5. **Total operating expenses** — direct + undistributed + base management fee.

Lines and departments with nothing in them are left out. A study that cannot be calculated yet shows a note instead of figures.

## Where the numbers come from
The calculation carried over from the old application (`calculateRoomRevenueAndGuestCount`): `directExpenses`, `Undistributed Operating Expenses`,
`CashOutReport › Management Fees › Base Management Fees` and `startUpAndPreOperationExpensesTotals › payments`. The page only adds the months of each calendar year.
Most lines carry their own `total`; the fixed and the disposable expenses carry one list of months per expense, and these are added together.

## Checked against the old application
Script `oracle/expense_page.php` + `oracle/expense_check.py` (kept outside the delivery): the saved result of the OLD application for the 21 studies of the database dump
was given to the new service, and every group total of every year was recomputed independently from the old monthly figures.

| What was compared | Result |
|---|---|
| Studies | 21 |
| Group totals compared (direct, undistributed, management, start-up) × years | 84 groups |
| Differences | 0 |
| Groups that held money (the rest are zero in the dump) | 11 |

A further hand-checked case: a study with a general fixed expense of 5,000 a month and a start-up cost of 12,000 paid in month 3, run with the new code:
2027 shows 60,000 for the fixed expense (12 months) and 12,000 in 2026 for the start-up — as calculated by hand.
The PHP test `ExpenseResultsTest` repeats a hand-checked case through the screens (rooms 10 % = 2,019,600; general 5 % = 1,009,800; fixed 50,000; base fee 3 % = 605,880; total 3,685,280).

## Known limits
- Calendar years, not financial years (the old income statement groups by the financial-year start month — that comes with step 12).
- The old calculation also produces expense months after the last month of the study (for example study 315 runs nine months past its end); the page, like the old statements, stops at the last month of the study.
- The incentive management fee is a percentage of EBITDA; the old application works it out in the income statement (step 12), so it is not here.
- Gaming, meetings and other-revenue expenses are not in the dump (no real data to compare); they use the same code as the others.
- "Disposable expenses" shows the calculation's purchase figure for the year, as the old application's income statement does.
