# QA verification report

- Project: `Slim`
- Verified at (UTC): `2026-10-22T11:40:49+00:00`
- Source root: `C:/Users/Admin/OneDrive/Documents/GitHub/Slim`
- Validator: codebase-qa-docs 1.0.0

## Summary

| Pass | Fail | Not Verified |
|---:|---:|---:|
| 17 | 0 | 4 (+10 scope limits below) |

**Result: no automated check failed.** This covers documentation-vs-source consistency only; it is not a statement that the application works.

## Checks

| # | Area | Check | Status | Detail |
|---:|---|---|---|---|
| 1 | Setup | facts.json and source-manifest.json parse | **Pass** |  |
| 2 | Outputs | All expected documents and diagrams exist (15) | **Pass** |  |
| 3 | Freshness | Docs are in sync with current source (SHA-256 of 145 files) | **Pass** |  |
| 4 | Read-only | Source tree unchanged during the generate run (hash before == after, 145 files) | **Pass** |  |
| 5 | Facts vs source | Every route cites a line that contains its path and a routing keyword (31 checked) | **Pass** |  |
| 6 | Facts vs source | Every dependency name appears at its cited manifest line (25 checked) | **Pass** |  |
| 7 | Facts vs source | Every table and column appears at its cited line | **Not Verified** | Nothing of this kind was extracted (0 items), so nothing could be checked. |
| 8 | Facts vs source | Every class/function name appears at its cited line (124 checked) | **Pass** |  |
| 9 | Facts vs source | Every foreign-key relation target appears at its cited line | **Not Verified** | Nothing of this kind was extracted (0 items), so nothing could be checked. |
| 10 | Facts vs source | Every env key appears at its cited line | **Not Verified** | Nothing of this kind was extracted (0 items), so nothing could be checked. |
| 11 | Docs vs source | Every `file:line` citation in the Markdown resolves to a real file/line (219 checked) | **Pass** |  |
| 12 | Docs vs source | Route rows in 07-apis.md equal the number of routes found (31) | **Pass** | rows in document: 31 |
| 13 | Docs vs source | Table sections in 09-database.md equal the number of tables found (0) | **Pass** | sections in document: 0 |
| 14 | Docs vs source | Dependency rows in 08-dependencies.md equal the number of dependencies found (25) | **Pass** | rows in document: 25 |
| 15 | Diagrams | api-routes.drawio: valid uncompressed mxfile, unique ids, all edges connect existing shapes (3 shapes) | **Pass** |  |
| 16 | Diagrams | api-routes.drawio: shape/edge evidence references resolve to real source lines (2 checked) | **Pass** |  |
| 17 | Diagrams | architecture.drawio: valid uncompressed mxfile, unique ids, all edges connect existing shapes (5 shapes) | **Pass** |  |
| 18 | Diagrams | architecture.drawio: shape/edge evidence references resolve to real source lines (1 checked) | **Pass** |  |
| 19 | Diagrams | Diagrams render correctly in draw.io (visual check) | **Not Verified** | Structure validated only. Open the files in https://app.diagrams.net or draw.io desktop to confirm layout. |
| 20 | Secrets | No secret values or credential-like literals in generated output (19 files scanned, 0 configured secret value(s) searched for) | **Pass** |  |
| 21 | Read-only | Generated output is stored separately from application source (outside the project tree) | **Pass** |  |

## Not Verified (scope limits)

| Item | Reason |
|---|---|
| Runtime behaviour of the application | Static analysis only; the application was never started. |
| Correct HTTP responses, status codes, schemas, authentication | No requests were sent. See qa/test-cases.md. |
| Live database schema, data and engine/version | The tool never connects to a database; only source definitions were parsed. |
| Execution/results of existing automated tests | Tests are listed by name only (53 files); none were run. |
| Business logic correctness and workflows | Cannot be derived from static structure. |
| Installed dependency versions / vulnerabilities | Only declared constraints read; lock files and advisories not consulted. |
| Dynamic routing, runtime-registered routes, route prefixes from groups/config | Only literal declarations on the cited line are captured. |
| Production deployment and server configuration | Out of scope; never accessed. |
| Graphify integration | Tool not available: Not on PATH and no graphify-out/graph.json in the project. Plugin runs fully without it; no Graphify-derived content is included. |
| draw.io desktop integration | Tool not available: not installed; .drawio files are plain XML and open at https://app.diagrams.net (File > Open). PNG/SVG export and visual rendering checks were NOT performed. |

## Suggested test cases

See [test-cases.md](test-cases.md) — functional and API cases derived from the source. All are **Not Verified** until executed.
