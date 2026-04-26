$route['lead-form/submit'] = 'lead_form/submit';
$route['lead-form/embed']  = 'lead_form/embed';
$route['lead-form/success']= 'lead_form/success';
```

**Step 2 — Place the controller**

Copy `Lead_form.php` to `application/controllers/Lead_form.php`.

**Step 3 — Change the token**

In the controller, replace `YOUR_SECRET_FORM_TOKEN_CHANGE_THIS` with any long random string. Keep it secret.

**Step 4 — Embed on any website**

Use whichever option suits the platform:

| Option | Best for |
|---|---|
| **Iframe** | WordPress, Wix, Squarespace — paste one line |
| **JS inject** | Custom sites wanting seamless look |
| **Direct POST form** | Any plain HTML site |
| **WordPress shortcode** | Paste code into `functions.php`, then use `[crm_lead_form]` on any page |

**Step 5 — Set the lead source per site**

Change the `?source=` parameter in the embed URL per platform:
```
?source=Google+Ads
?source=Facebook+%2F+Meta+Ads
?source=LinkedIn

--------------------------------------------------------------------------------------------

<!-- ============================================================
     OPTION A — Iframe Embed (WordPress, Wix, any platform)
     Paste anywhere in a page / widget / shortcode block.
     Change the `src` URL to your actual CRM domain.
     ============================================================ -->

<iframe
  src="https://YOUR-CRM-DOMAIN.com/lead-form/embed?source=Website+Inquiry"
  width="100%"
  height="640"
  frameborder="0"
  scrolling="auto"
  style="border:none; width:100%; max-width:600px; display:block; margin:0 auto;">
</iframe>



<!-- ============================================================
     OPTION B — JavaScript Inject (advanced, no iframe border)
     Add this to your page's <body> where you want the form.
     ============================================================ -->

<div id="crm-lead-form"></div>
<script>
  fetch('https://YOUR-CRM-DOMAIN.com/lead-form/embed?source=Website+Inquiry')
    .then(r => r.text())
    .then(html => {
      // Extract just the inner content and inject it
      var tmp = document.createElement('div');
      tmp.innerHTML = html;
      var form = tmp.querySelector('.lf-wrap');
      if (form) document.getElementById('crm-lead-form').appendChild(form);

      // Re-execute inline scripts from the fetched HTML
      tmp.querySelectorAll('script').forEach(function(old) {
        var s = document.createElement('script');
        s.textContent = old.textContent;
        document.body.appendChild(s);
      });
    });
</script>



<!-- ============================================================
     OPTION C — Direct POST form (no JS fallback)
     Works even without JavaScript. Redirects to thank-you page.
     ============================================================ -->

<form method="POST" action="https://YOUR-CRM-DOMAIN.com/lead-form/submit">
  <input type="hidden" name="form_token"  value="YOUR_SECRET_FORM_TOKEN_CHANGE_THIS">
  <input type="hidden" name="lead_source" value="Website Inquiry">

  <!-- Honeypot -->
  <input type="text" name="website_url" style="display:none" tabindex="-1">

  <input type="text"  name="practice_name"  placeholder="Practice Name *" required>
  <input type="email" name="contact_email"   placeholder="Email *" required>
  <input type="tel"   name="contact_phone"   placeholder="Phone">
  <input type="text"  name="contact_person"  placeholder="Your Name">
  <textarea           name="practice_needs"  placeholder="How can we help?"></textarea>

  <button type="submit">Send Message</button>
</form>



<!-- ============================================================
     WORDPRESS SHORTCODE PLUGIN (optional, paste into functions.php)

     After adding the code below to your theme's functions.php,
     use [crm_lead_form] on any page/post.
     ============================================================ -->

<?php
// Paste this in your WordPress theme's functions.php
function crm_lead_form_shortcode($atts) {
    $atts = shortcode_atts([
        'source' => 'Website Inquiry',
        'height' => '640',
    ], $atts);

    $url    = 'https://YOUR-CRM-DOMAIN.com/lead-form/embed';
    $source = urlencode($atts['source']);
    $height = (int)$atts['height'];

    return '<iframe src="' . esc_url($url) . '?source=' . $source . '" '
         . 'width="100%" height="' . $height . '" frameborder="0" '
         . 'style="border:none;width:100%;max-width:600px;display:block;margin:0 auto;">'
         . '</iframe>';
}
add_shortcode('crm_lead_form', 'crm_lead_form_shortcode');

// Usage on any page: [crm_lead_form]
// Custom source:     [crm_lead_form source="Google Ads" height="700"]
?>



<!-- ============================================================
     ALL SUPPORTED lead_source VALUES
     Pass any of these as the ?source= query parameter
     ============================================================ -->

Website Inquiry
Referral - Existing Client
Referral - Partner
LinkedIn
Cold Email Campaign
Cold Calling
Google Ads
Facebook / Meta Ads
Conference / Event
Webinar
Industry Directory
Upwork / Freelancer
Inbound Call
Email Inquiry
Marketing Campaign
Vendor Partner
Reseller / Affiliate
Other