<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// ── Normalize to $S (same pattern as support view) ───────────────────────────
$S = [];
if (isset($existing) && is_array($existing))        $S = $existing;
elseif (isset($existing_data) && is_array($existing_data)) $S = $existing_data;

$aiEnabled      = $S['ai_enabled']       ?? '0';
$aiProvider     = $S['ai_provider']      ?? '';
$aiModel        = $S['ai_model']         ?? '';
$aiApiKey       = $S['ai_api_key']       ?? '';
$aiMaxTokens    = $S['ai_max_tokens']    ?? '1024';
$aiTemperature  = $S['ai_temperature']   ?? '0.3';
$aiSystemPrompt = $S['ai_system_prompt'] ?? '';

// ── Provider catalogue ────────────────────────────────────────────────────────
$providers = [
    ''       => '— Select Provider —',
    'claude' => 'Anthropic Claude',
    'openai' => 'OpenAI (ChatGPT)',
    'groq'   => 'Groq (Free tier — Llama)',
    'gemini' => 'Google Gemini',
];

$providerKeyUrls = [
    'claude' => 'https://console.anthropic.com/settings/keys',
    'openai' => 'https://platform.openai.com/api-keys',
    'groq'   => 'https://console.groq.com/keys',
    'gemini' => 'https://aistudio.google.com/app/apikey',
];

$providerHints = [
    'claude' => 'Haiku = fastest & cheapest · Sonnet = balanced · Opus = most capable.',
    'openai' => 'GPT-4o Mini recommended for cost efficiency.',
    'groq'   => 'Free tier with rate limits — ideal for testing before committing.',
    'gemini' => 'Flash has a free tier · Pro offers higher quality responses.',
];

// ── All models per provider (used to build the model <select> server-side) ───
$allModels = [
    'claude' => [
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fastest, cheapest)',
        'claude-sonnet-4-5'         => 'Claude Sonnet 4.5 (Balanced)',
        'claude-opus-4-5'           => 'Claude Opus 4.5 (Most capable)',
    ],
    'openai' => [
        'gpt-4o-mini'   => 'GPT-4o Mini (Fast, cheap)',
        'gpt-4o'        => 'GPT-4o (Most capable)',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Legacy)',
    ],
    'groq' => [
        'llama-3.1-70b-versatile' => 'Llama 3.1 70B (Versatile)',
        'llama-3.1-8b-instant'    => 'Llama 3.1 8B (Fastest)',
        'mixtral-8x7b-32768'      => 'Mixtral 8x7B (Large context)',
        'gemma2-9b-it'            => 'Gemma 2 9B',
    ],
    'gemini' => [
        'gemini-1.5-flash'     => 'Gemini 1.5 Flash (Free tier, fast)',
        'gemini-1.5-pro'       => 'Gemini 1.5 Pro (Most capable)',
        'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
    ],
];

$defaultPrompt = 'You are an expert RCM (Revenue Cycle Management) assistant for a medical billing company. Help staff with ICD-10 codes, CPT codes, denial management (CARC/RARC codes), claim submission, ERA/EOB reconciliation, payer guidelines, and AR workflows. Always cite specific codes when answering. Keep responses concise and actionable.';

// Tooltip helper — same signature as support view
if (!function_exists('ai_tt')) {
    function ai_tt(string $label, string $tip): string {
        return $label .
            ' <i class="ti ti-info-circle text-primary align-middle"'
            . ' data-bs-toggle="tooltip" data-bs-placement="top"'
            . ' title="' . htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') . '"></i>';
    }
}
?>

<div class="card-body">
  <div class="mb-3">
    <p class="text-muted mb-0">
      Configure the AI assistant that appears as a floating chat widget for all
      logged-in users. Supports Anthropic Claude, OpenAI, Groq, and Google Gemini.
    </p>
  </div>

  <!-- ── Enable / disable toggle ───────────────────────────────────────────── -->
  <div class="settings-section mb-4">
    <div class="d-flex align-items-center justify-content-between
                border rounded p-3"
         style="background:#f8f9fa">
      <div>
        <div class="fw-semibold" style="font-size:.9rem">
          <?= ai_tt('Enable AI Assistant', 'When enabled, a floating chat widget appears for all logged-in users.') ?>
        </div>
        <div class="text-muted" style="font-size:.8rem">
          Show the RCM AI chat widget to all logged-in staff
        </div>
      </div>
      <div class="form-check form-switch mb-0 ms-3">
        <input class="form-check-input"
               type="checkbox"
               role="switch"
               name="settings[ai_enabled]"
               id="aiEnabled"
               value="1"
               <?= $aiEnabled === '1' ? 'checked' : '' ?>>
      </div>
    </div>
  </div>

  <!-- ── Provider + Model ──────────────────────────────────────────────────── -->
  <div class="settings-section mb-4">
    <h6 class="section-title mb-3">Provider &amp; Model</h6>
    <div class="row app-form g-3">

      <!-- Provider -->
      <div class="col-md-6">
        <label class="form-label">
          <?= ai_tt('AI Provider', 'Select which AI service to use. Each requires its own API key.') ?>
        </label>
        <select name="settings[ai_provider]"
                class="form-select"
                id="aiProvider">
          <?php foreach ($providers as $val => $label): ?>
            <option value="<?= htmlspecialchars($val) ?>"
                    data-key-url="<?= htmlspecialchars($providerKeyUrls[$val] ?? '') ?>"
                    data-hint="<?= htmlspecialchars($providerHints[$val] ?? '') ?>"
                    <?= $aiProvider === $val ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted" id="providerHint" style="font-size:.75rem">
          <?= htmlspecialchars($providerHints[$aiProvider] ?? '') ?>
        </small>
      </div>

      <!-- Model -->
      <div class="col-md-6">
        <label class="form-label">
          <?= ai_tt('Model', 'The specific model to use. Different models trade off speed vs capability vs cost.') ?>
        </label>
        <select name="settings[ai_model]"
                class="form-select"
                id="aiModel">
          <?php if ($aiProvider && isset($allModels[$aiProvider])): ?>
            <?php foreach ($allModels[$aiProvider] as $mVal => $mLabel): ?>
              <option value="<?= htmlspecialchars($mVal) ?>"
                      <?= $aiModel === $mVal ? 'selected' : '' ?>>
                <?= htmlspecialchars($mLabel) ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="">— Select a provider first —</option>
          <?php endif; ?>
        </select>
      </div>

    </div>
  </div>

  <!-- ── API Key ───────────────────────────────────────────────────────────── -->
  <div class="settings-section mb-4">
    <h6 class="section-title mb-3">API Key</h6>
        <div class="row app-form align-items-end g-2">
        
          <div class="col-md-12">
            <label class="form-label mb-1">
              <?= ai_tt('API Key', 'Your API key is encrypted before being stored in the database.') ?>
            </label>
        
            <div class="input-group input-group-sm">
        
              <input type="password"
                     class="form-control font-monospace"
                     name="settings[ai_api_key]"
                     id="aiApiKey"
                     autocomplete="off"
                     placeholder="<?= $aiApiKey !== '' ? 'Key saved — paste new to replace' : 'Paste API key...' ?>">
        
              <button type="button"
                      class="btn btn-outline-secondary"
                      id="aiApiKeyToggle">
                <i class="ti ti-eye" id="aiApiKeyEyeIcon"></i>
              </button>
        
              <a href="#"
                 class="btn btn-outline-secondary d-none"
                 id="aiGetKeyLink"
                 target="_blank">
                <i class="ti ti-external-link"></i>
                Get API 
              </a>
        
              <button type="button"
                      class="btn btn-primary"
                      id="aiTestBtn">
                <i class="ti ti-plug me-1"></i>Test Connection
              </button>
        
            </div>
        
            <div class="mt-1 small text-muted">
              <?php if ($aiApiKey !== ''): ?>
                <span class="text-success ms-2">
                  <i class="ti ti-lock me-1"></i>Already Saved
                </span>
              <?php endif; ?>
              (Leave blank to keep existing key.)
            </div>
          </div>
        
        </div>
        
        <div id="aiTestResult" class="mt-2 small rounded px-2 py-2 d-none"></div>
  </div>

  <!-- ── Tuning ─────────────────────────────────────────────────────────────── -->
  <div class="settings-section mb-4">
    <h6 class="section-title mb-3">Response Tuning</h6>
    <div class="row app-form g-3">

      <div class="col-md-4">
        <label class="form-label">
          <?= ai_tt('Max Response Tokens', 'Controls the maximum length of each AI reply. 1024 is a good default for RCM Q&A.') ?>
        </label>
        <input type="number"
               name="settings[ai_max_tokens]"
               class="form-control"
               min="256"
               max="4096"
               step="128"
               value="<?= (int)$aiMaxTokens ?>">
        <small class="text-muted" style="font-size:.75rem">Range: 256 – 4096</small>
      </div>

      <div class="col-md-4">
        <label class="form-label">
          <?= ai_tt('Temperature', '0 = precise and deterministic. 1 = creative and varied. 0.3 is recommended for RCM.') ?>
        </label>
        <input type="number"
               name="settings[ai_temperature]"
               class="form-control"
               min="0"
               max="1"
               step="0.1"
               value="<?= number_format((float)$aiTemperature, 1) ?>">
        <small class="text-muted" style="font-size:.75rem">Range: 0.0 – 1.0</small>
      </div>

    </div>
  </div>

  <!-- ── System Prompt ─────────────────────────────────────────────────────── -->
  <div class="settings-section mb-2">
    <h6 class="section-title mb-1">
      <?= ai_tt('System Prompt', 'This instruction is sent to the AI before every conversation. Defines its role, tone, and scope.') ?>
      <button type="button"
              class="btn btn-link btn-sm p-0 ms-2 text-muted"
              style="font-size:.75rem;vertical-align:baseline"
              id="aiResetPrompt">
        Reset to default
      </button>
    </h6>
    <textarea name="settings[ai_system_prompt]"
              id="aiSystemPrompt"
              class="form-control font-monospace"
              rows="9"
              style="font-size:.78rem;line-height:1.6"
              placeholder="Describe the AI assistant's role, tone and scope..."><?= htmlspecialchars(
                  $aiSystemPrompt !== '' ? $aiSystemPrompt : $defaultPrompt,
                  ENT_QUOTES, 'UTF-8'
              ) ?></textarea>
    <small class="text-muted" style="font-size:.75rem">
      Editing the prompt lets you specialise or restrict the assistant's behavior
      without changing any code.
    </small>
  </div>

  <hr class="mt-4">
</div>

<script>
(function () {
  'use strict';

  /* ── Static model list (mirrors PHP $allModels) ────────────────────────── */
  const ALL_MODELS = {
    claude: {
      'claude-haiku-4-5-20251001' : 'Claude Haiku 4.5 (Fastest, cheapest)',
      'claude-sonnet-4-5'         : 'Claude Sonnet 4.5 (Balanced)',
      'claude-opus-4-5'           : 'Claude Opus 4.5 (Most capable)',
    },
    openai: {
      'gpt-4o-mini'   : 'GPT-4o Mini (Fast, cheap)',
      'gpt-4o'        : 'GPT-4o (Most capable)',
      'gpt-3.5-turbo' : 'GPT-3.5 Turbo (Legacy)',
    },
    groq: {
      'llama-3.1-70b-versatile' : 'Llama 3.1 70B (Versatile)',
      'llama-3.1-8b-instant'    : 'Llama 3.1 8B (Fastest)',
      'mixtral-8x7b-32768'      : 'Mixtral 8x7B (Large context)',
      'gemma2-9b-it'            : 'Gemma 2 9B',
    },
    gemini: {
      'gemini-1.5-flash'     : 'Gemini 1.5 Flash (Free tier, fast)',
      'gemini-1.5-pro'       : 'Gemini 1.5 Pro (Most capable)',
      'gemini-2.0-flash-exp' : 'Gemini 2.0 Flash (Experimental)',
    },
  };

  const PROVIDER_HINTS = {
    claude : 'Haiku = fastest & cheapest · Sonnet = balanced · Opus = most capable.',
    openai : 'GPT-4o Mini recommended for cost efficiency.',
    groq   : 'Free tier with rate limits — ideal for testing before committing.',
    gemini : 'Flash has a free tier · Pro offers higher quality responses.',
  };

  const DEFAULT_PROMPT = <?= json_encode($defaultPrompt) ?>;
  const SAVED_MODEL    = <?= json_encode($aiModel) ?>;
  const TEST_URL       = '<?= site_url('ai_chat/test') ?>';

  /* ── DOM ──────────────────────────────────────────────────────────────── */
  const providerEl   = document.getElementById('aiProvider');
  const modelEl      = document.getElementById('aiModel');
  const hintEl       = document.getElementById('providerHint');
  const apiKeyEl     = document.getElementById('aiApiKey');
  const eyeBtn       = document.getElementById('aiApiKeyToggle');
  const eyeIcon      = document.getElementById('aiApiKeyEyeIcon');
  const getKeyLink   = document.getElementById('aiGetKeyLink');
  const testBtn      = document.getElementById('aiTestBtn');
  const testResult   = document.getElementById('aiTestResult');
  const resetBtn     = document.getElementById('aiResetPrompt');
  const promptEl     = document.getElementById('aiSystemPrompt');

  /* ── Rebuild model <select> when provider changes ─────────────────────── */
  function rebuildModels(provider, selectValue) {
    const models = ALL_MODELS[provider] || {};
    const keys   = Object.keys(models);

    modelEl.innerHTML = '';

    if (!keys.length) {
      modelEl.innerHTML = '<option value="">— Select a provider first —</option>';
      return;
    }

    keys.forEach(function (val) {
      const opt      = document.createElement('option');
      opt.value      = val;
      opt.textContent = models[val];
      if (val === selectValue) opt.selected = true;
      modelEl.appendChild(opt);
    });

    // If nothing matched, default to first
    if (!modelEl.value) modelEl.selectedIndex = 0;
  }

  /* ── Provider <select> change ─────────────────────────────────────────── */
  providerEl.addEventListener('change', function () {
    const provider = this.value;
    const opt      = this.options[this.selectedIndex];

    // Update hint text
    hintEl.textContent = PROVIDER_HINTS[provider] || '';

    // Update "Get Key" link
    const keyUrl = opt.dataset.keyUrl || '';
    if (keyUrl) {
      getKeyLink.href = keyUrl;
      getKeyLink.classList.remove('d-none');
    } else {
      getKeyLink.classList.add('d-none');
    }

    // Clear the key field when provider switches (force re-entry)
    apiKeyEl.value = '';

    // Rebuild model dropdown — no saved value yet on a provider switch
    rebuildModels(provider, '');

    // Clear test result
    testResult.classList.add('d-none');
  });

  /* ── Show / hide API key ──────────────────────────────────────────────── */
  eyeBtn.addEventListener('click', function () {
    const isPass    = apiKeyEl.type === 'password';
    apiKeyEl.type   = isPass ? 'text' : 'password';
    eyeIcon.className = isPass ? 'ti ti-eye-off' : 'ti ti-eye';
  });

  /* ── Test connection ──────────────────────────────────────────────────── */
  testBtn.addEventListener('click', async function () {
    const provider = providerEl.value;
    const model    = modelEl.value;
    const apiKey   = apiKeyEl.value.trim();

    if (!provider || !model) {
      showTestResult(false, 'Select a provider and model first.');
      return;
    }
    if (!apiKey) {
      showTestResult(false, 'Paste your API key to test the connection.');
      return;
    }

    testBtn.disabled = true;
    testBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Testing…';
    testResult.classList.add('d-none');

    try {
      const fd = new FormData();
      fd.append('provider', provider);
      fd.append('api_key',  apiKey);
      fd.append('model',    model);

      const res  = await fetch(TEST_URL, { method: 'POST', body: fd });
      const data = await res.json();

      showTestResult(
        data.success,
        data.success
          ? '✓ Connected — ' + (data.model || model)
          : (data.error || 'Connection failed.')
      );
    } catch (e) {
      showTestResult(false, 'Network error — check your connection.');
    }

    testBtn.disabled  = false;
    testBtn.innerHTML = '<i class="ti ti-plug me-1"></i> Test Connection';
  });

  function showTestResult(ok, msg) {
    testResult.className = 'mt-2 rounded px-2 py-2 '
      + (ok ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger');
    testResult.innerHTML = '<i class="ti ti-'
      + (ok ? 'circle-check' : 'alert-circle') + ' me-1"></i>' + msg;
    testResult.classList.remove('d-none');
  }

  /* ── Reset system prompt ──────────────────────────────────────────────── */
  resetBtn.addEventListener('click', function () {
    promptEl.value = DEFAULT_PROMPT;
  });

  /* ── Init: restore model dropdown for saved provider ─────────────────── */
  (function init() {
    const provider = providerEl.value;
    if (!provider) return;

    // Restore get-key link
    const opt    = providerEl.options[providerEl.selectedIndex];
    const keyUrl = opt ? opt.dataset.keyUrl : '';
    if (keyUrl) {
      getKeyLink.href = keyUrl;
      getKeyLink.classList.remove('d-none');
    }

    // Rebuild model list and select the saved value
    rebuildModels(provider, SAVED_MODEL);
  })();

  /* ── Bootstrap tooltips ───────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      .forEach(function (el) { new bootstrap.Tooltip(el); });
  });

})();
</script>