# Bgomesweb_GeminiProvider

Base module for Google Gemini (Generative AI) API integration in Magento 2. Provides a reusable HTTP client with model fallback, response sanitization, cost calculation and centralized configuration, to be consumed by other modules (e.g. `Bgomesweb_AiAssistant`).

## Features

- `GeminiClientInterface::generateContent()` — single entry point for content generation.
- **Model fallback** — tries the configured models in order and retries on recoverable errors (429/500/503).
- **Cost calculation** — per-model input/output pricing (USD per 1M tokens) converted to BRL.
- **Exchange rate cron** — updates the USD→BRL rate on a configurable schedule.
- **Event** `bgomesweb_ai_request_completed` — dispatched after each request so other modules can log usage and cost.
- Response sanitization.

## Requirements

- Magento 2.4+
- PHP 8.2+
- `guzzlehttp/guzzle` ^7.0

## Installation

```bash
composer require bgomesweb/module-gemini-provider
bin/magento module:enable Bgomesweb_GeminiProvider
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Stores → Configuration → Bgomesweb → Gemini AI Provider** (scopes: default, website, store view)

### General

| Field | Default | Description |
| :--- | :--- | :--- |
| Enable Gemini Provider | No | Enables/disables the module. |
| Google Generative AI API Key | — | Your Gemini API key (stored encrypted). |
| Models (Fallback Order) | `gemini-2.5-flash-lite`, `gemini-2.5-flash`, `gemini-2.5-pro`, … | Sortable list of models; the order defines the fallback sequence. |
| Enable Model Fallback | No | Retry with the next model on failure. |
| API Request Timeout (Seconds) | 60 | HTTP timeout. |
| Max Output Tokens | 8192 | Upper bound for generated tokens. |

### Pricing Settings

| Field | Default | Description |
| :--- | :--- | :--- |
| USD to BRL Exchange Rate | 5.30 | Used for cost calculation. |
| Exchange Rate Update Schedule | `0 */6 * * *` | Cron expression for the rate update job. |
| Pricing per Model | — | Input/output cost per model. |

## Usage

Inject `Bgomesweb\GeminiProvider\Api\GeminiClientInterface` in your class and call `generateContent()`. It returns a `GenerationResultInterface` with the generated content and usage data.

## License

Proprietary — © 2025 Bruno Gomes ([bgomesweb.com.br](https://www.bgomesweb.com.br/))
