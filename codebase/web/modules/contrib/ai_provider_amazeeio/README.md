# amazee.ai AI Provider

The **amazee.ai AI Provider** module integrates amazee.ai's AI services into Drupal, enabling seamless AI functionalities within your Drupal applications.
This provider acts as a bridge between the Drupal AI module ecosystem and amazee.ai's AI capabilities, facilitating operations such as text generation, translation, and more.

## Features

- **Seamless Integration**: Connects Drupal's AI module with amazee.ai's AI services.
- **Access to LLMs AND vectorDBs**: It automatically provides you with access to multiple services and spins up a vectorDB for you to leverage.
- **Configurable Settings**: Provides an admin interface for configuring API credentials and settings.
- **Support for Various AI Operations**: Enables operations like text generation, translation, and other AI-driven functionalities.
- **Recipe Support**: Includes a [configuration recipe](https://www.drupal.org/project/ai_provider_amazeeio_recipe) for automated setup.
- **Your Own Hosting**: Integrates with your existing hosting provider – no migration needed.
- **Easy Setup!**: 5-minute setup with minimal configuration required.

## Data Protection

Unlock the power of Drupal AI while keeping complete control over your data.We offer a simple, data-sovereign, enterprise-grade AI solution designed for Drupal, ensuring compliance, security, and zero lock-in.

- Complete data sovereignty with regional processing.
- Fully managed AI infrastructure handles the technical back-end.
- Built on open source best practices.

You can choose your region:

- 🇩🇪 Germany
- 🇬🇧 United Kingdom
- 🇨🇭 Switzerland
- 🇺🇸 United States
- 🇦🇺 Australia

Other regions can be added, just ask!

## Requirements

- **Drupal Core**: ^10.3 or ^11
- **AI Module**: The core AI module must be installed and enabled.
- **Key Module**: For managing API keys securely.
- **amazee.ai Account**: Valid credentials for accessing amazee.ai's AI services. It is simple to do right inside the Provider Module if you don't already have an account.

## Installation

1. **Using Composer**:

   ```bash
   composer require drupal/ai_provider_amazeeio:^1.0@beta
   ```

2. **Enable the Module**:

   ```bash
   drush en ai_provider_amazeeio
   ```

3. **Configure the Provider**:

   - Navigate to `/admin/config/ai/settings`.
   - Select "amazee.ai" as your AI provider.
   - Enter your amazee.ai API credentials.

## Configuration Recipe

For streamlined setup, utilize the [amazee.ai AI Provider Recipe](https://www.drupal.org/project/ai_provider_amazeeio_recipe):

1. **Install the Recipe Module**:

   ```bash
   composer require drupal/ai_provider_amazeeio_recipe:^1.0@beta
   ```

2. **Apply the Recipe**:

   ```bash
   drush recipe:apply amazeeio_ai_provider
   ```

This will automatically configure the necessary settings for the amazee.ai AI Provider.

## Usage

Once configured, the amazee.ai AI Provider will handle AI operations initiated by the Drupal AI module. You can utilize various AI functionalities such as:

- **Text Generation**: Generate content using AI-driven prompts.
- **Translation**: Translate content between languages.
- **Content Suggestions**: Receive AI-based content recommendations.
- **Video Extraction**: Extract Anything
- **Customized AI-generated Audio from text**: Use a RAG DB of transcripts to create an integrated but new synopsis of lots of talks.
- **DrupalAI Agents**: Create complex workflows and have a swarm of agents complete the tasks in those workflows.
- **Pre-moderate Content**: AI can read, flag, and prepublish content for you.
- **Compliance Checking**: Worried about bad words? You can use AI as a compliance checker.
- **Deep Crawling**: Gain additional context to get better answers to questions.
- **Fact Checking**: Use AI to read articles, check the details, and only publish if they are factually true.
- **Convert Video into Text**: Scrape a video and turn it into an article.
- **Locations from Text**: Use AI to verify that a location exists and have it populate an address field.
- **Auto Categorize Content**: AI can quickly and efficiently scan your text and apply appropriate categories or tags to that content.
- **Chatbot/Assistants**: A chatbot can replace your site search and do it better than traditional search.
- **Tone Adjustment**: Rewrite content to match a formal, friendly, academic, or promotional tone.
- **Image Alt Text Generation**: Automatically add Alt Text to your images.
- **AI Content Generation**: Automatically generate page content, summaries, and metadata using integrated LLMs directly in the node editing experience.
- **Multilingual Translation**: Translate content into multiple languages with AI-powered translation models embedded in the Drupal UI.
- **Simplify Text**: Automatically rewrite content for lower reading levels or different cognitive styles.
- **Custom LLM Deployment**: Use self-hosted or enterprise LLMs (via amazee.ai or another provider) for compliance, data sovereignty, or cost efficiency.
- **Create Web Form from a sketch**: Take a picture of a drawing you've made of a form, and have AI generate the Web Form for you.
- **Accessibility Auditing**: Identify accessibility issues in content (e.g., missing headings, improper link text) using natural language inspection.
- **Visual QA Assistant**: Automatically scan designs or layout previews and flag inconsistencies, contrast issues, or spacing problems.

This is just a small sampling of what can be achieved.
For more ideas see:

- **[Workflows of AI](https://workflows-of-ai.com/)**
- **[Official DrupalAI Module Page](https://www.drupal.org/project/ai)**
- **[Overview of Drupal AI Modules](https://www.drupal.org/project/artificial_intelligence_initiative/issues/3429343)**

These functionalities can be accessed through the AI module's interfaces or programmatically via Drupal's APIs.

## Development

For developers interested in extending or customizing the amazee.ai AI Provider:

- **Repository**: [GitLab - ai_provider_amazeeio](https://git.drupalcode.org/project/ai_provider_amazeeio)
- **Issue Queue**: [Drupal.org Issue Queue](https://www.drupal.org/project/issues/ai_provider_amazeeio)

Contributions, bug reports, and feature requests are welcome.

## Maintainers

- **Andrew Belcher**: Initial creator and maintainer.
- **FreelyGive Development**: Supporting organization.

![ ](https://static.scarf.sh/a.png?x-pxid=2e5c5cce-8f26-4eae-9dfb-12573ed08431)
