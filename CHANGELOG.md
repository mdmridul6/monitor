# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- Initial release

## [1.0.0] - 2026-10-08
### Added
- Laravel Monitor package with secure heartbeat telemetry
- Service provider for automatic initialization
- Configuration system with environment variable support
- Stable application ID generation with persistent storage
- HTTP client for sending telemetry to central monitoring API
- Queued job system for non-blocking heartbeats
- Artisan commands for manual heartbeat sending and status checking
- Comprehensive test suite covering security, reliability, and edge cases
- Detailed documentation including API contract and example server
- Security-focused design that never transmits sensitive data