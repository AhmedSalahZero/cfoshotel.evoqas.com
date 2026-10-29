# CFO Hotels — handover guide (end of the rebuild)

## What it is
The hotel feasibility study of the previous CFO Hotels application, rebuilt on Laravel 12 / PHP 8.4, Vue 3 (Inertia) and MySQL, as a PWA, in the Zavero design.

## The flow of a study (the tabs)
Study → Revenue (rooms, food and beverage, gaming, meetings, others) → Expenses → Results (revenue results, expense results) → Investment (property, land and construction, furniture and equipment, loans) → Statements (receivables, inventory, fixed assets, prepaid, taxes, insurance, loan schedule) → Financial statements (income statement, cash in/out, balance sheet, ratios) → Dashboard. "Compare two studies" is on the Studies list.

## Where things are
- Screens: `resources/js/Pages/Studies/` (one page for each screen), shared parts in `resources/js/Components/` (Revenue, Expenses, Investment, Statements, Reports, Charts, Form).
- Styling: `resources/css/design/` (revenue.css holds the study pages, statements, reports, dashboard and the table look).
- Words: `resources/js/lang/en.js` and `ar.js` (always change both; a test checks they match).
- Calculation: `app/Calculations/` (loans, depreciation, seasonality, collections, IRR, intervals), `app/Services/` (revenue, expenses, statements, reports), `app/Models/Concerns/HospitalitySectorAccessor.php` (the study's own figures).
- Excel Model (the download icon in the Studies list): `app/Excel/`, `app/Http/Controllers/ExcelModelController.php`, see `docs/EXCEL_MODEL.md`.
- Details of each part: the other files in `docs/`.

## Tests
`php artisan test` (PHP) and `npm test` (Vitest). Both must be green before any delivery.

## Running it on a developer machine
Start the app with `composer serve` instead of `php artisan serve`: it is the same server with Xdebug switched off. On a machine where Xdebug is on (`xdebug.mode=develop`) every calculation is about four times slower (the income statement of the test study: about 8 s instead of 2 s).
The financial statements, the ratios and the dashboard share one kept calculation (`StudyCache` name `cash-in-out`): after a save the first of these pages works it out, the others reuse it, and a page seen before comes back at once until study data is written again.

## Where the new app differs from the old one (on purpose)
- Gross Profit Margin in the ratios uses the corrected formula of step 06; the EBIT card of the comparison was corrected; the comparison no longer fails on a division by zero.
- The income statement taxes section shows Corporate Taxes only (the payments are in the cash report).
- Buying land or a property by installments, with the payments in different months, now balances (Check Error 0 in every month). The old application only did when everything was paid in the same month or in one installment: `InstallmentMethod` kept writing the first installment date, the land / property payable was only listed on the purchase month, the loan withdrawal of a later payment was worked out against the previous month instead of the previous payment, the land loan balance before its schedule started was lost, and the interest of the withdrawal period was never added to the cost of the land / property. Cash purchases give the same figures as before.
- The stock on hand when the hotel opens (the "beginning inventory" of a disposable line) is paid in cash in the first operating month, so it has a source of funds (the old application showed it as stock with nothing behind it, 10.6M of Check Error in the test study). Supplier payables are not touched.
- A hotel that is built but whose Property Acquisition Costs page was never saved cannot carry the cost of the building to the fixed assets when it opens (the useful life of the building is read from that page). The financial pages and the dashboard now say so, with a link; no value is made up.
- Construction and FF&E loan drawings are the payment itself once the equity is used up (the old application used the balance of the previous month, so two shares due 30 and 120 days after the work drew more than the cost). The loan balance at the end of the study, taken out of the terminal value of the owners' valuation, is the balance of the schedule at that month (it was always 0 in the first version of this app).
- "Save & next" on every input page follows the order of the old application (`App\Support\StudyFlow`), and leaves out the streams the study switched off.
- Balances at the end of a quarter, half year or year (`IntervalEndBalancesService`) are an exact port of the old class. In the old app the balance sheet and ratios stopped with an error for a financial year starting in April or July; here they work.

## Things the old application did that remain as they were
- The financial year start month (January, April, July) does not move the quarters, half years and years: they are always calendar periods (a slip in the old `IntervalSummationOperations`). Making an April year group April–March would be a change of the calculation, not part of the rebuild.
- The property status (Existing, Existing need refurbishing, Under development) is only a label; no calculation reads it.
- There is no opening balance sheet: the balance sheet starts from zero at the study start. An opening balance sheet for an existing property is a possible next feature.

## Left out on purpose
Income Statement Planning and the other features listed in the roadmap sheet "Left out", including route 87 (`update-hospitality-sector-duration-type`), which no hotel screen used.

## Cleaned in step 13
About 480 unused translation entries (products, assets, opening, raw payments, old dashboard, sensitivity, sharing, report, payback and the old manpower page), 226 unused style rules, and two unused components (`Components/ZChart.vue`, `Components/ModalDialog.vue`, since deleted).

## Cleaned on 2026-10-29
The server message files `lang/en|ar/costs.php`, `opening.php`, `products.php`, `sharing.php` and `projects.php` (left over from the other product: manpower, opening balances, products, share links; no key of them was used anywhere) and the old diagnostic `tools/balance_check.php` (replaced by `tests/Feature/BalanceSheetCheckTest.php`).
