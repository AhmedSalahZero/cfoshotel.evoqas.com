# Statements (Step 11)

Read-only screens, one controller (`StatementsController`) and one page (`Studies/Statement.vue`) for the 16 statement screens of the previous application.
URL: `/studies/{id}/statements/{family}/{type}`. Anyone who may view the study can open them.

| Family | Types | Old title (kept in English) |
|---|---|---|
| collection (Receivables) | rooms, foods, gaming*, meetings*, others*, total | `<Type> Receivables Statement Report` |
| inventory | rooms, foods, gaming*, total | `<Type> Inventory Statement` |
| disposables | rooms, foods, gaming*, total | `<Type> Disposable Payment Statement` |
| prepaid (Fixed expenses) | general, marketing, property, energy, total | `<Type> Fixed Expense Statement Report` |
| fees | one | `Incentive Management Fees Statement Report` |
| taxes | property, insurance, corporate | `<…> Statement Report` |
| assets | assets, suppliers, payable (construction) | `Fixed Assets Statement Report` … |
| loans | property, land, hard-construction, ffe | `<…> Loan Schedule Report` |

\* only when the study uses that stream; otherwise the statement does not exist (404), as in the old application where it crashed.

## How the figures are made
- `App\Services\Statements\ReportItems` is the old report chain (`onlyMonthlyDashboardItems` / `formatDashboardReportItems`) ported once. Step 12 (reports and dashboards) reuses it.
- `App\Services\Statements\StatementService` puts the lines into tables the way the old controllers did (monthly, quarterly, semi-annually, annually).
- Figures reach the page both as numbers (`values`, for colour and trends) and as text (`text`, exactly as the server formatted them).
- Helpers added to `app/Support/helpers.php`: `formatDateForView`, `convertZeroToTotalString`, `removeTotalTableIfFirstIsZero`, `removeKeyFromArray`, `sumFourArray`, `sumFiveArrays`, `zeroValueForIndexesBeforeIndex`.

## Checked against the old application
Six studies (317, 306, 313, 314, 307, 315), every statement the old application can produce, every interval: about 55,000 cells (about 20,000 not zero), **0 differences**.
Where the old application itself crashes (no land acquisition for suppliers, a stream that is off, FFE without a transferred date for taxes and insurance) the new page shows an empty statement or a 404 instead; these were not "fixed" in the figures.

## The table (`StatementTable.vue`)
Years on a band above the months, cells wear the colour of their year; each kind of line has a colour (opening, additions, outflows, subtotals, closing, balances); heat tint per line (switch off); little trend line; fixed name column, headings and summary column; the hovered column is lit; Σ / First / Last summary; copy to a spreadsheet and CSV download; compact rows; works right-to-left and in dark mode.

## Deviations and notes
- Old spellings are kept on the loan headings ("Begining Balance", "Principle Amount") because field names must equal the old application. Say if you want them corrected.
- The totals row of a loan schedule is aligned under its own columns.
- Arabic wording is new and needs a native review (`resources/js/lang/ar.js`, block `stmt`).
- Not verified (as in Step 10): loans with step-up/step-down/grace or quarterly/semi-annual installments, because the old application crashes on them.

## Tests
`tests/js/statements.spec.js` (helpers, page, loan schedule, Arabic) and `tests/Feature/StatementsTest.php` (404s, access, menu, page shape). The PHP test is only syntax checked here; run `php artisan test --filter=StatementsTest`.
