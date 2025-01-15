<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office">
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <style>
    td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    .mso-break-all {word-break: break-all;}
  </style>
  <![endif]-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    .hover-bg-violet-100:hover {
      background-color: #ede9fe;
    }
    .hover-bg-violet-200:hover {
      background-color: #ddd6fe;
    }
    .hover-bg-violet-800:hover {
      background-color: #5b21b6;
    }
    @media (max-width: 600px) {
      .sm-p-6 {
        padding: 24px;
      }
      .sm-px-4 {
        padding-left: 16px;
        padding-right: 16px;
      }
      .sm-px-6 {
        padding-left: 24px;
        padding-right: 24px;
      }
    }
  </style>
</head>
<body style="margin: 0; width: 100%; background-color: #f8fafc; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word">
  <div role="article" aria-roledescription="email" aria-label lang="en">
    <div class="sm-px-4" style="background-color: #f8fafc; font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif">
      <table align="center" style="margin: 0 auto" cellpadding="0" cellspacing="0">
        <tr>
          <td style="width: 552px; max-width: 100%">
            <div role="separator" style="line-height: 24px">&zwj;</div>
            <table style="width: 100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="sm-p-6" style="border-radius: 8px; background-color: #fffffe; padding: 24px 36px; border: 1px solid #e2e8f0">
                  <a href="https://metrifi.com">
                    <img src="https://assets.parceldns.com/assets/118d2255-e25f-414f-921e-07c62befe1fc/e6b49881-bb5e-4d7c-9f04-ffeb0d0e8660.png?parcelPath=/metrifi-logo-w-purple-icon-1.png" width="120" alt="MetriFi" style="max-width: 100%; vertical-align: middle">
                  </a>
                  
                  <div role="separator" style="line-height: 34px">&zwj;</div>

                  <h1 style="margin: 0 0 8px; font-size: 24px; line-height: 1.5">
                    <span style="color: #4c1d95">
                        Weekly update:
                    </span>
                    <br>
                    <span style="font-weight: 700; color: #7c3aed">
                        Your website is currently missing out on {{ '$' . number_format($organization->assets['median']['total_potential'] * 13.03, 0) }} per year
                    </span>
                  </h1>

                  <p style="margin: 0 0 24px; font-size: 12px; color: #64748b">
                    Based on 28 day period {{ $period }}
                  </p>

                  <a href="https://app.metrifi.com" class="hover-bg-violet-200" style="margin-bottom: 8px; display: inline-block; border-radius: 9999px; background-color: #ede9fe; padding: 12px 24px; font-size: 14px; color: #4c1d95; text-decoration: none">
                    Explain this to me
                  </a>

                  <div role="separator" style="height: 1px; line-height: 1px; background-color: #cbd5e1; margin-top: 24px; margin-bottom: 24px">&zwj;</div>

                  <h2 style="margin: 0 0 4px; font-size: 20px; color: #4c1d95">
                    Biggest losers
                  </h2>

                  <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569">
                    Your funnels missing out on the most assets.
                  </p>

                  @foreach ($dashboards as $dashboard)
                  <div style="position: relative; margin-bottom: 16px; overflow: hidden; border-radius: 8px; background-color: rgba(245, 243, 255, 0.7); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    <div style="width: 100%;">
                        <div style="padding-left: 16px; padding-right: 16px; padding-top: 16px;">
                            <div style="display:table;width:100%;table-layout:fixed">
                                <div style="display:table-cell;vertical-align:middle;width:30px">
                                    <img src="https://assets.parceldns.com/assets/118d2255-e25f-414f-921e-07c62befe1fc/549dacb6-1b59-4d23-945a-c8f8e42e04ff.png?parcelPath=/graph-icon-2.png" width="30" style="max-width: 100%;">
                                </div>
                                <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #4c1d95;">
                                        {{ $dashboard['name'] }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div role="separator" style="height: 1px; line-height: 1px; background-color: #cbd5e1; margin-top: 16px; margin-bottom: 0;">&zwj;</div>
                        <div style="padding-left: 16px; padding-right: 16px; padding-bottom: 20px; margin-top: 16px; margin-bottom: 0;">
                            <p style="margin: 0; font-size: 16px; line-height: 24px; font-weight: 700; color: #4c1d95;">
                                {{ '$' . number_format($dashboard['median_analysis']['subject_funnel_potential_assets'] * 13.03, 0) }} per year
                            </p>
                            <p style="margin-bottom: 20px; font-size: 16px; line-height: 24px; color: #475569;">
                                Step {{ $dashboard['median_analysis']['bofi_step_index'] + 1 }} of your funnel is converting {{ $dashboard['median_analysis']['bofi_performance'] }}% lower than comparisons ({{ $dashboard['median_analysis']['bofi_conversion_rate'] }}% vs {{ $dashboard['median_analysis']['bofi_median_of_comparisons'] }}%)
                            </p>
                            <a href="https://app.metrifi.com/{{ $organization['slug'] }}/{{ $dashboard['id'] }}" class="hover-bg-violet-100" style="display: inline-block; border-radius: 9999px; background-color: #fffffe; padding: 8px 16px; font-size: 14px; color: #4c1d95; text-decoration: none; border: 1px solid #ddd6fe;">
                                View dashboard
                            </a>
                        </div>
                    </div>
                </div>
                  @endforeach

                  <div role="separator" style="line-height: 24px">&zwj;</div>

                  <p style="margin: 0 0 8px; font-size: 18px; color: #475569">
                    Talk with us about how you can improve your website.
                  </p>

                  <div>
                    <a href="https://app.metrifi.com" style="display: inline-block; text-decoration: none; padding: 16px 24px; font-size: 16px; line-height: 1; border-radius: 100px; color: #fffffe; margin-top: 8px; margin-bottom: 8px; background-color: #7c3aed" class="hover-bg-violet-800">
                      <!--[if mso]><i style="mso-font-width: 150%; mso-text-raise: 31px" hidden>&emsp;</i><![endif]-->
                      <span style="mso-text-raise: 16px">Let's talk</span>
                      <!--[if mso]><i hidden style="mso-font-width: 150%">&emsp;&#8203;</i><![endif]-->
                    </a>
                  </div>

                  <div role="separator" style="line-height: 24px">&zwj;</div>

                  <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569">
                    Cheers,
                    <br>
                    <span style="font-weight: 600; color: #4c1d95">MetriFi</span>
                  </p>
                </td>
              </tr>
            </table>
            <table style="width: 100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="sm-px-6" style="padding: 24px 36px">
                  <p style="margin: 0; font-size: 12px; color: #64748b">
                    &copy; 2025 MetriFi. All rights reserved.
                    <a href="https://app.metrifi.com" style="color: #64748b; text-decoration: underline">Unsubscribe</a>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>