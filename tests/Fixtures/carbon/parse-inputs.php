<?php

// Inputs for Carbon::make() / Carbon::parse() differences between Carbon 2 and 3.
return ['0', ' 0 ', '0 ', '', ' ', null, false, true, 0, 0.0, 1, '00', '1', '-1', '1.5', '0.0', 'now', 'today', 'tomorrow', 'yesterday',
    '2026-01-01', '01-01-2026', '1/1/2026', '01/13/2026', '13/01/2026', '31-02-2026', '2026-02-30', '2026-10-19 10:00:00', '19-10-2026',
    'abc', '@0', '@1700000000', '0000-00-00', '1970-01-01', 'P1D', 'R2/2026-01-01T00:00:00Z/P1D', '20261019', '2026-10', '10-2026',
    'first day of next month', 'last day of this month', '+1 day', 'next monday', '01-10-2026 00:00:00', '2024-02-29', 'Jan 2026', '1 Jan 2026'];
