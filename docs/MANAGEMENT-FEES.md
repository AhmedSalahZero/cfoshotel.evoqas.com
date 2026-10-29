# Step 09C — Management fees

Screen: `/studies/{study}/management-fees` (last tab of the Expenses group).

Two tables, each with one fixed line and a percentage for every operating year (0–100):
- **Base Management Fees As % From Revenues** — shows the total revenues of the hotel above the line.
- **Incentive Management Fees As % From EBITDA** — no revenue row (as the old screen).

Stored as the old app stored it: table `management_fees`, `model_name` = `management`, section names
`base_management_fees_as_percentage_from_revenues` / `incentive_management_fees_as_percentage_from_revenues`,
names "Base Management Fees" / "Incentive Management Fees", `payload` = percentage by year index (the last year repeats to the end, done by the model).
The existing calculation (`getBaseManagementFee`, `getIncentiveManagementFee`) reads these rows unchanged.

Rules: every year needs a percentage between 0 and 100. The names are fixed. Saving again changes the same two rows (kept by id).
Differences from the old app: none in what is stored; the old screen deleted and re-created both rows on every save, this one updates them.
Tests: `tests/Feature/ManagementFeesTest.php` (7, PHP), `tests/js/management-fees.spec.js` (4).
