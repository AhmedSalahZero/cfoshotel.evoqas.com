# Step 12 — Financial statements, dashboard and comparison

## Screens
| Tab (order) | Screen | Route name |
|---|---|---|
| Study, Revenue, Expenses, Results, Investment, Statements | (earlier steps) | |
| Financial statements | Income statement, Cash in/out, Balance sheet, Ratios | `studies.reports.show` (`/studies/{study}/reports/{report}`; report = income, cash, balance, ratios) |
| Dashboard | Study dashboard (feasibility cards, revenue and gross-profit charts, ADR / RevPAR / occupancy, profitability) | `studies.dashboard.show` |
| (Studies list link) | Compare two studies | `studies.compare` |

## Data flow
Calculated results (steps 09–11) → `ReportItems` → `FinancialReports` (cash in/out, balance sheet) and `StudyAnalysis` (ratios, dashboard) → `ReportService` → controller → Inertia page.
`ComparisonService::compare(first, second)` binds each study's date tables, then builds the investment table and six cards (revenues, gross profit, EBITDA, EBIT, EBT, net profit) in thousands with variance amount and percent.

## Checked against the old app
Income statement, cash, balance sheet and ratios: identical on 6 studies (about 91,000 cells). Dashboard: identical on 6 studies, 2 variants each. Comparison: identical on two studies in both directions, except the corrected EBIT card.

## Deviations (intentional)
- Compare, EBIT card: old app used the wrong source; corrected.
- Compare: division by zero is guarded (shows 0.00 %).
- Compare: year labels come from the first study's calendar.
- Ratios, Gross Profit Margin: uses the Step 06 corrected formula.
- Income statement taxes section shows Corporate Taxes only (payments belong to cash).

## Interval end balances (resolved)
The old `IntervalEndBalancesService.php` was received and ported exactly (months 3/6/9/12 for quarters, 6/12 for half years, 12 for years; an incomplete interval gives no figure).
Re-checked against the old app with the real old class: income, cash, balance sheet and ratios identical on 6 studies (about 93,000 cells), dashboard identical.
For a study whose financial year starts in April or July the old balance sheet and ratios stopped with an error; here they work, with calendar quarters, half years and years like the income and cash statements (the old sums do the same because of a slip in `IntervalSummationOperations`).
`IntervalBeginningBalancesService.php` is not used by any screen of the study and was not rebuilt.

## Tests
`tests/js/reports.spec.js` (16), `tests/Feature/ReportsTest.php` (5, to be run with `php artisan test`). Menu-position regexes updated in 8 earlier feature tests.
