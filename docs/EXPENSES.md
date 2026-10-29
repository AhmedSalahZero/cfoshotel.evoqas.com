# Step 09A / 09B — Expense input pages

Nine screens, one engine: `/studies/{study}/expenses/{page}` where page is
`rooms`, `foods`, `casinos`, `meetings`, `others`, `general`, `marketing`, `property`, `energy`.

## What each screen holds
| Page | Tables |
|---|---|
| Rooms | Variable % of rooms revenue · cost per night sold · cost per guest · disposable expenses · manpower |
| Food & beverage | Variable % of F&B revenue · cost per guest · disposable · manpower |
| Gaming / Meetings / Other revenue | Variable % of the facility revenue · cost per night (guest) · disposable · manpower (gaming, other only) |
| General & admin / Sales & marketing | Variable % of total revenue · fixed monthly expenses · manpower (one switch shared by both) |
| Property / Energy | Variable % of total revenue · fixed repeating monthly expenses (property also: taxes & insurance) |

Gaming, Meetings and Other appear only when the study uses that revenue stream (404 otherwise).

## Where the data goes
Same table as the old app: `department_expenses` (`model_name` rooms, foods, gaming, meetings, other, general, marketing, property, energy).
The "expense at operating date" and "net salary at operation date" are computed by the server on save
(amount × (1 + escalation %)^years-to-opening), because the calculation reads the saved figure, as the old app did.
Gross salary used by the calculation = net ÷ (1 − taxes % − insurance %).

## Rules
- A line needs a name to be saved; a line with a name must be complete. Clearing the name removes the line.
- Percentages of one year add up to 100 or less. A name may not repeat in a table.
- Cash % of disposable expenses 0–100; deferred = 100 − cash. Coverage / due days from 0, 15, 30 … 180.
- Taxes + social insurance together stay under 100 %. Currencies are the study's own.
- Property taxes and insurance are always two fixed lines; the payment month belongs to the taxes.

## Deliberate differences from the old app
1. Answering **No** to "Do you want to add Manpower Expense?" removes that page's positions when you save
   (the old app kept calculating hidden positions). Positions found in old data count as **Yes**.
2. Lines are kept by id, so editing a line never changes the id the calculation sees.
3. Screen words corrected: "Epenses" → "Expenses". All other field names are the reference project's.

## Limits
- The head-count table shows up to 48 operating months; the last month is repeated afterwards (as the old app).
- Arabic text of these screens is a first draft and needs review by the owner.
- Tests: `tests/js/expenses.spec.js` (7), `tests/Feature/ExpensesTest.php` (13, PHP — syntax-checked only in the build environment).
- Not yet built: management fees (09C), start-up cost (09D), expense results page.
