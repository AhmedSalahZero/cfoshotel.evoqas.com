# Excel Model

**What it is.** A button (a download icon) next to every study in **Studies** gives the study as a live `.xlsx`: every result is a formula, so
changing a yellow input cell recalculates everything after it, down to the balance sheet and the dashboard. Same idea as the Excel model of the
manufacturing application.

- Route `GET /studies/{study}/excel-model` (`studies.excel`), `App\Http\Controllers\ExcelModelController@download`.
- Anyone who may **view** the study may download it (owner, a colleague it is shared with, the company administrator). A study of another company
  is "not found". A study the application cannot calculate yet goes back to the list with a message.
- File name `CFO-Hotels-Model-<study>-<en|ar>.xlsx`. The words follow the language of the account (Arabic sheets run right to left).
- Sheets are protected **without a password** (Review > Unprotect Sheet); only the yellow input cells are open.
- Development command: `php artisan hotels:model-xlsx <study> <path> [--lang=ar]`.

## The sheets (tab order)
| Tab | What it holds |
|---|---|
| Cover | brand, place for a logo, the study, key results, the balance-sheet check, colour legend, how to use, contents with links |
| Dashboard | key figures (investment, equity, loans, WACC, NPV and IRR of the firm and of the owners, payback), year-by-year summary, ratios |
| Study, Calendar | dates, days, operating months, exchange rate by year, tax / return / growth rates |
| Rooms, Food & Beverage, Gaming, Other Revenue, Meeting Spaces, Revenue | nights sold, rates, guests and revenue of each stream; total revenue |
| Collections | collection terms of every stream: collected and still owed (receivables) |
| Rooms / F&B / Gaming / Meeting / Other / General / Marketing / Property / Energy Expenses | % of revenue, per night, per guest, fixed (paid in advance), manpower, disposables (stock, purchases, payables) |
| Start-up Costs | start-up and pre-operating costs, written off over 12 months |
| Investment | hard and soft construction and FF&E: the work, the payments, equity and loan split |
| Land & Property | the purchase of the land and of the property: payment method, equity and loan split |
| Loans, Loan schedules | the four loans: drawing with its interest, the schedule (grace, capitalisation, steps), balance, interest, capitalised interest |
| Fixed assets | projects under progress, building, FF&E (property and items), land, depreciation and replacement |
| Fees & taxes | base management fee, property taxes and insurance with what is owed |
| Income statement | revenue to net profit, month by month (incentive fee and corporate tax fall in December) |
| Cash flow | cash in and out, working capital injection, management fee and corporate tax owed, free cash flow |
| Balance sheet | assets, liabilities, loans, owners equity, **Check Error** (zero in every month) |

## Proof
`tests/Feature/ExcelModelAccuracyTest.php` builds the workbook, has LibreOffice recalculate it (`tests/Support/Recalc.php`) and compares **every month
of every line** with the application's own engine: revenue, collections, expenses (all kinds), start-up, construction and FF&E (every method),
land and property purchases (cash, installments, custom), the four loans (every type, interval, grace, step), assets with replacements, fees,
taxes and insurance, the income statement, the cash flow (including the working capital injection), the balance sheet, the ratios, NPV, IRR and
payback. It runs on the five fixture studies and on variants of study 313 changed in the database (rolled back after each test); it was also run
on the real study 6 (15 years): the differences are below 0.2 on a balance sheet of 5.7 billion, and Check Error is 0. Another test edits input
cells inside the file and compares with the same change made in the application, and one checks that no cell is an error in either language.

`ExcelModelStructureTest` checks names, tab order, references between sheets, protection without a password, Arabic right-to-left, formulas
(no PHP left in them) and that every word exists in English and Arabic. `ExcelModelDownloadTest` checks who may download, the file name, the
language, and a study that cannot be calculated. The accuracy test skips itself where `soffice` (LibreOffice) is not installed.

## Rules of the workbook
- The shape of the study is fixed in the file (grey cells): study start, duration, development and opening months, the number of room types,
  facilities, expense lines and FF&E items, the chosen methods (payment method, execution curve, loan type, installment interval, collection and
  seasonality type), the tenor of each loan. Every number inside them is open. To change the shape, change the study in the application and
  download again.
- No charts.
- The workbook gives the **application's** figures, quirks included, not "textbook" ones; where the application had a defect it was fixed in the
  application first (see below), so both agree.

## Defects found in the application while building it (fixed)
- Construction and FF&E loan drawings used the equity balance of the previous **month**, not of the previous **payment**: when payments are not in
  consecutive months (two shares due 30 and 120 days after the work) the loan drew more than the cost (87M against 84M). Fixed in both applications.
- The loan balance at the end of the study (used to take the loans out of the terminal value) was always 0 in the new application; it is now the
  balance of the schedule at the end of the study.
- The equity pays the payments **in the order the application keeps them** (the down payment month, then each month of work with its first share
  before its second), not in calendar order: the workbook follows that order (`sq` row of the Investment sheet).

## Things learnt from the engine (worth knowing)
- The rate at opening is `rate x (1 + escalation) ^ (days between the study start and the opening / 365)`.
- Every facility of a stream is priced in the currency of the FIRST facility of that stream.
- Other revenue grows by the escalation until opening every year; the gaming "daily target" is the guest-capture percentage of the year.
- Meeting seasonality: weight of the month divided by the weights of the operating months of its calendar year.
- Loan drawing: interest runs on the balance **including the new drawing** for the days since the **previous drawing month** (the first has none);
  the schedule starts in the last drawing month; the construction loan's interest in that month is the drawing interest of the last period; the
  FF&E loan's interest drops the drawing interest.
- Depreciation: every addition and every replacement is written off over its own run of months (the replacement of an FF&E item with the
  building finished before opening is written off from the opening, ending `life` months after the replacement - a quirk of the application);
  the net book value of a month only counts the write-off of the additions made up to that month.
- The incentive fee and the corporate tax are worked out once a year from the calendar year and fall in December; the corporate tax is only
  charged when the study has an incentive fee line.
- The valuation numbers its years (and counts its months for the payback) from the first month something is paid or collected; a last year that
  the study leaves before its December is left out.
- When work or a purchase falls **after** the opening, the application's balance sheet does not balance (interest is capitalised and charged); the
  workbook gives the same figures, Check Error included.

## Where the code is
`app/Excel/*` (own .xlsx writer: `ZipWriter`, `Workbook`, `Sheet`, `Styles`, `Theme`, `Grid`, `K`, `Ctx`, `Labels`), `ModelDataBuilder` (study ->
saved rows), `ModelData` (calendar facts), `ModelBuilder` (the order the sheets are built in), one class per sheet in `app/Excel/Sheets/`, words
in `app/Excel/LabelsData.php`.
