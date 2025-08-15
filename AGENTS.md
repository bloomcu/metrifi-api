# Funnels and Google Analytics

## Funnel and Steps
- A `Funnel` belongs to an `Organization` and a Google Analytics `Connection` and stores an ordered set of `FunnelStep` records.
- Updating a funnel or step (metrics, order) triggers `FunnelSnapshotAction` and re-analysis of dashboards so asset totals stay up to date.
- Each `FunnelStep` uses `FunnelStepMetricsCast` to expand its `metrics` JSON column into a collection with default keys such as `pagePath`, `linkUrl`, and `hostname`.
- Supported metrics, all based on GA4 user counts:
  - `pageUsers` – matches `unifiedPagePathScreen`.
  - `pagePlusQueryStringUsers` – matches `unifiedPageScreen`.
  - `pageTitleUsers` – matches `unifiedScreenName`.
  - `outboundLinkUsers` – matches both `linkUrl` and `unifiedPagePathScreen`.
  - `formUserSubmissions` – `eventName` `form_submit` with additional parameter filters (`form_destination`, `form_id`, `form_length`, `form_submit_text`) and page path.

## Creating Steps
- `GenerateFunnelStepsAction` can derive steps from a terminal URL; it segments the path, validates traffic via the `pageUsers` report, and creates ordered steps each seeded with a `pageUsers` metric.
- Steps may also be added manually by specifying a name and array of metrics.
- Metrics within a step are ORed together: a user counts for the step if they satisfy **any** metric definition.

## Viewing Funnels
- `GoogleAnalyticsDataService::funnelReport($funnel, $startDate, $endDate, $disabledSteps)` builds a `runFunnelReport` request for the GA4 property tied to the funnel's connection.
- Each metric becomes a GA **funnel filter expression** using `EXACT` string matches.
  - Complex metrics such as `outboundLinkUsers` and `formUserSubmissions` generate nested `andGroup` expressions so all required fields must match.
- The response is parsed into `report.steps` with user counts, per-step conversion rates, overall conversion rate, and calculated assets; disabled steps are removed before rates are computed.

## Exploring Google Analytics
- `GoogleAnalyticsDataController` exposes endpoints that proxy to the service for ad‑hoc exploration: `pageUsers`, `pagePlusQueryStringUsers`, `pageTitleUsers`, `outboundLinkUsers`, `outboundLinkByPagePathUsers`, `formUserSubmissions`, and `llmUsers`.
- Use these endpoints to discover dimension values when defining funnel step metrics.

## Key Files
- `app/App/Services/GoogleAnalyticsData/GoogleAnalyticsDataService.php` – builds GA requests, translates funnel metrics, and parses responses.
- `app/Http/Services/GoogleAnalytics/GoogleAnalyticsDataController.php` – exposes GA exploration endpoints.
- `app/Domain/Funnels/Funnel.php` & `app/Domain/Funnels/FunnelStep.php` – models with snapshot/analysis hooks.
- `app/Domain/Funnels/Casts/FunnelStepMetricsCast.php` – casts the `metrics` JSON attribute with defaults for each metric type.
- `app/Domain/Funnels/Actions/GenerateFunnelStepsAction.php` – helper that auto‑generates steps from a terminal page path.
