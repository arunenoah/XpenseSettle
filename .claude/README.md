# Claude Agent System - PetApp Backend

## Overview

This directory contains a specialized agent workflow system designed for the PetApp Backend Laravel application. The system implements a structured approach to feature development with defined roles and responsibilities tailored to our Laravel 12/PHP 8.2 stack.

## Agent System Structure

### **6 Specialized Agents**

1. **tech-lead** (Red) - Strategic oversight and Laravel technical design
2. **tech-writer** (Purple) - Laravel documentation creation and drift analysis  
3. **senior-engineer** (Blue) - Laravel feature implementation and coding
4. **code-reviewer** (Green) - Laravel code quality and standards review
5. **qa-tester** (Yellow) - Laravel quality assurance and testing
6. **security-reviewer** (Orange) - Laravel security audits and vulnerability assessments

## Quick Start Guide

### **Starting a New Feature**

1. **Invoke the tech-lead agent**:
   ```
   "I need to implement [feature description] for the pet application system"
   ```

2. **Tech-lead will**:
   - Create a design document: `[feature-name].md`
   - Define Laravel architecture (models, services, controllers)
   - Specify FLKitOver integration requirements
   - Hand off to tech-writer for initial documentation

3. **Follow the automated workflow** through each development phase

### **Agent Customization for PetApp Backend**

This agent system is specifically configured for:

- **Laravel 12** with PHP 8.2+ strict typing
- **UUID architecture** throughout the application
- **FLKitOver integration** for document signing
- **AWS services** (S3, SES, Twilio)
- **Security standards** (rate limiting, domain validation)
- **Testing infrastructure** (PHPUnit with factories)

## Workflow Process

```
Feature Request
    ↓
tech-lead (Laravel Design & Planning)
    ↓
tech-writer (Initial Documentation)
    ↓
senior-engineer + code-reviewer (Implementation & Review Cycles)
    ↓
security-reviewer (Security Audit & Vulnerability Assessment)
    ↓
tech-lead (Checkpoint Validation)
    ↓
qa-tester + senior-engineer (Testing & Bug Fix Cycles)
    ↓
tech-lead (Final Validation)
    ↓
tech-writer (Final Documentation & Drift Analysis)
    ↓
Complete Feature
```

## PetApp Backend Specific Standards

### **Coding Standards**
- `declare(strict_types=1);` at top of every PHP file
- Comprehensive PHPDoc with type hints
- UUID primary keys for all models
- Service layer composition pattern
- Dependency injection with constructor property promotion

### **Security Standards**
- Domain validation middleware
- Multi-tier rate limiting
- Input validation using Form Requests
- Environment variable configuration
- Comprehensive audit logging

### **Testing Standards**
- PHPUnit for unit and feature tests
- Database factories for test data
- SQLite in-memory database for fast tests
- Integration testing for FLKitOver workflows

### **Documentation Standards**
- Comprehensive API documentation with examples
- Migration documentation
- Environment variable documentation
- Drift analysis for design vs implementation

## Usage Examples

### **Example 1: New API Endpoint**
```
User: "I need to add an endpoint for landlords to approve pet applications"

Tech-lead: Creates design document with:
- New controller method in LandlordController
- Approval service method
- Database migration for approval tracking
- FLKitover integration for final documentation
- Security considerations for landlord authorization
```

### **Example 2: FLKitOver Integration**
```
User: "I need to integrate a new document type from FLKitOver"

Tech-lead: Creates design document with:
- FLKService method additions
- Document model relationships
- Webhook handling for document status
- S3 storage for document files
- Email notifications for document completion
```

### **Example 3: Security Enhancement**
```
User: "I need to add rate limiting to the pet application submission"

Tech-lead: Creates design document with:
- Rate limiting middleware configuration
- Database tracking for rate limit violations
- Error handling for rate limited requests
- Logging for security monitoring
```

## Agent Interaction Patterns

### **Autonomous Work Cycles**
- **Implementation**: senior-engineer ↔ code-reviewer (max 3 cycles)
- **Testing**: qa-tester ↔ senior-engineer (max 3 cycles)
- **Escalation**: Automatic escalation to tech-lead after max cycles

### **Quality Gates**
1. **Code Review Gate**: All code must pass code-reviewer approval
2. **Security Gate**: All implementations must pass security-reviewer audit
3. **Testing Gate**: All features must pass qa-tester validation
4. **Documentation Gate**: Complete documentation with drift analysis

### **Handoff Points**
- tech-lead → tech-writer (initial documentation)
- tech-writer → senior-engineer (implementation ready)
- senior-engineer → security-reviewer (security audit ready)
- security-reviewer → tech-lead (security audit complete)
- tech-lead → qa-tester (ready for testing)
- qa-tester → tech-writer (testing complete)

## File Structure

```
.claude/
├── agents/
│   ├── tech-lead.md          # Laravel technical design and oversight
│   ├── tech-writer.md        # Documentation and drift analysis
│   ├── senior-engineer.md    # Laravel implementation and coding
│   ├── code-reviewer.md      # Laravel code quality review
│   ├── qa-tester.md          # Laravel testing and QA
│   └── security-reviewer.md  # Security audits and vulnerability assessment
└── README.md                 # This file
```

## Configuration Files Referenced

The agents are configured to work with these project files:

- `composer.json` - Dependencies and scripts
- `phpunit.xml` - Testing configuration
- `config/middleware.php` - Security middleware configuration
- `database/migrations/` - Schema definitions
- `app/Services/` - Business logic layer
- `app/Http/Controllers/Api/` - API controllers
- `tests/Feature/` and `tests/Unit/` - Test suites

## Best Practices

### **For Users**
1. Always start with tech-lead for new features
2. Provide clear business requirements
3. Be available for clarification during development
4. Review final documentation and drift analysis

### **For Development Team**
1. Trust the autonomous work cycles
2. Focus on validation at checkpoints
3. Maintain high quality standards
4. Keep documentation updated

### **For Process Improvement**
1. Review drift analysis for pattern insights
2. Update agent configurations based on lessons learned
3. Maintain coding standards documentation
4. Continuously improve testing coverage

## Troubleshooting

### **Common Issues**
- **Agent not responding**: Check that agent files are properly formatted
- **Workflow stuck**: Escalate to tech-lead for manual intervention
- **Quality standards not met**: Review code-reviewer checklist
- **Testing failures**: Check qa-tester test environment setup

### **Getting Help**
1. Review the specific agent documentation
2. Check existing feature examples in the codebase
3. Consult the project's ARCHITECTURE_REVIEW.md
4. Refer to API_SECURITY_STANDARDS.md for security requirements

## Integration with CI/CD

This agent system integrates with your existing GitLab CI/CD pipeline:

- Automated testing when qa-tester approves
- Code quality checks when code-reviewer approves
- Security audits when security-reviewer approves
- Documentation updates when tech-writer completes
- Deployment triggers when tech-lead gives final approval

## Future Enhancements

Potential additions to the agent system:

- `performance-analyst.md` - Performance testing and optimization
- `devops-engineer.md` - Deployment and infrastructure automation
- `accessibility-reviewer.md` - Accessibility compliance and testing


This agent system ensures consistent, high-quality Laravel development while maintaining our established patterns and standards for the PetApp Backend application.
