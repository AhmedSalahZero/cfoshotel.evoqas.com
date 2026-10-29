# CFO Hotels — Calculation classes (Step 06)

Location: `app/Calculations/`. Every class is a port of the previous CFO Hotels (`app/ReadyFunctions`), with the same
formulas, so the same study gives the same numbers. The proof is in `tests/Unit/CalculationsStep06Test.php` and
`tests/Fixtures/hotel/step06_cases.json` (80 inputs with the answers the OLD code gave).

## The last seven classes (this delivery)

| Class | What it does |
|---|---|
| `CalculateIrrService` | IRR (rate that makes the present value of the cash flows zero) and NPV. |
| `CalculatePaybackPeriodService` | The month the owners' money is back. `__calculate()` is the old answer ("5.8 Years" or "More Than 7 Years"); `estimate()` is the one the screens use (see note 6). |
| `CalculateProfitsEquationsService` | Gross profit, EBITDA, EBIT, EBT, net profit, each with its % of revenue. |
| `RatioAnalysisService` | Profitability, liquidity, efficiency and leverage ratios, month by month. |
| `FixedAssetsPayableEndBalance` | Fixed-asset suppliers: beginning balance, purchases, payments, end balance (monthly, quarterly, semi-annually, annually). |
| `PropertyInsurancePayableEndBalance` | Same, for property insurance. |
| `PropertyTaxesPayableEndBalance` | Same, for property taxes. |

## Decisions and notes

1. **Rounding.** PHP 8.4 can differ from PHP 8.1 by one unit on a few numbers (1,400,937 instead of 1,400,938). Decided:
   this does not matter for hotel financial planning. The 500,000-number proof (two .gz files, 8.6 MB) was removed;
   `LegacyMath` stays and has a small readable test.
2. **IRR (decision of the owner).** The IRR is calculated by the MathPHP library (`Finance::irr`) everywhere in the
   application, as the study dashboard of the old application did. The old application also had a home-made IRR search
   that was switched off in the dashboard; it was not carried over, so the same cash flows always give the same IRR.
   `CalculateIrrService::calculateIrr()` returns NAN when there is no answer (all cash flows positive or all negative,
   or a single value): the screen must check `is_nan()` before showing it.
   The library is installed with the command `composer require markrogoyski/math-php`.
3. **Gross profit margin (corrected, decision of the owner).** The old application showed the EBITDA margin on the
   "Gross Profit Margin" line of the ratio analysis (a variable was overwritten), so the real gross profit margin was never
   shown. Now "Gross Profit Margin" = Gross Profit ÷ Net Sales and "EBITDA Margin" = EBITDA ÷ Net Sales. This is the only
   ratio that differs from the old reports; all others are identical.
4. **Empty profit lines.** When nothing is passed in, the old EBIT / EBT / net profit stopped with an error; the new ones
   return empty lists. Numbers with data are the same.
5. **Wording.** The screens no longer print the old "More Than 7 Years" text; they use `estimate()` and the `payback` words of en.js / ar.js.
6. **Payback beyond the study.** A hotel may repay after the study ends. `estimate()` then carries the last 12 months' cash
   flow forward, growing each year at the study's perpetual growth rate, until the money still missing is covered
   (`extra years = ln(1 + missing x g / next-year cash) / ln(1 + g)`, or `missing / next-year cash` when g = 0).
   Answers: `within` (exact month), `estimated` (shown with a "≈" and a note), `never` (the last year brings in nothing, or the
   shrinking flow cannot add up) and `capped` (more than 50 years: "More than 50 years"). For the discounted payback the caller passes the
   discounted cash and the growth net of the required return, `(1 + g) / (1 + r) - 1`. The estimate assumes the last year is typical,
   with no large refurbishment after the study, the same assumption the perpetual growth already makes.