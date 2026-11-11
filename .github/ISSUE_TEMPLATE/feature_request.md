name: Feature Request
description: Suggest an idea or enhancement for a specific site component
title: '[FEATURE]: '
labels: ['enhancement']
body:
  - type: dropdown
    id: site
    attributes:
      label: Site
      description: Select the site component this feature applies to.
      options:
        - Lineups
        - Events
        - Countdowns
    validations:
      required: true

  - type: input
    id: target-url
    attributes:
      label: Target URL
      description: Provide the full URL where this feature or issue is located.
      placeholder: https://example.com/lineups
    validations:
      required: true

  - type: textarea
    id: current-behavior
    attributes:
      label: Current Behavior
      description: Describe what the site currently does or how it behaves right now.
      placeholder: Currently, when you click...
    validations:
      required: true

  - type: textarea
    id: proposed-behavior
    attributes:
      label: Proposed Behavior
      description: What exactly would you like to see it do instead?
      placeholder: It should instead...
    validations:
      required: true

  - type: textarea
    id: additional-context
    attributes:
      label: Additional Context / Screenshots
      description: Add any other context or visual mockups here.
    validations:
      required: false
