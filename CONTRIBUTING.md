# Contributing

Read [development workflow](docs/DEVELOPMENT.md) and [architecture](docs/ARCHITECTURE.md).

Create one short-lived branch per change from `main`: `feat/<purpose>`,
`fix/<purpose>`, `docs/<purpose>` or `chore/<purpose>`. Keep commits focused and
use messages such as `fix(checkout): reject missing delivery addresses`. Describe
the problem, behavior and relevant validation in the pull request. Do not invent
historical commits or combine unrelated changes to manufacture development stages.

Merge through pull requests after required checks pass. Use a merge commit to
preserve the branch's individual commits and its development stage. Do not force
push shared branches. Resolve conflicts on the branch, rerun affected checks,
then merge. See development instructions for bisect and revert.

Preserve public contracts and stored data unless an explicit migration accompanies
the change. Escape output, validate input, enforce server-side permissions and
nonces, and keep WooCommerce as the commerce source of truth. Protect credentials
and never run database integration scripts against a live restaurant.

Add meaningful regression coverage for behavior changes, not tests duplicating
implementation. Run the relevant suite plus CI's baseline checks. Report checks
that were not run and distinguish mocked services from live verification.
