# Documentation Index

Documentation for Beepi Vehicle Lookup WordPress plugin.

## 📚 Quick Navigation

### Main Documentation (Root)
- **[Main README](../README.md)** - Plugin overview, features, and quick start
- **[Development Notes](./replit.md)** - Recent changes and development log

### Architecture & Assessment
- **[Architecture](./architecture/ARCHITECTURE.md)** - System design, data flows, technical details
- **[Assessment](./architecture/ASSESSMENT.md)** - Current state analysis, strengths, and issues
- **[Testing Guide](./architecture/TESTING_GUIDE.md)** - Testing strategies and manual test procedures

### Refactoring Plans
- **[Refactor Plan](./refactoring/REFACTOR_PLAN.md)** - Main refactoring roadmap
- **[Admin Refactor Plan](./refactoring/ADMIN_REFACTOR_PLAN.md)** - Detailed admin class refactoring plan
- **[Admin Refactor README](./refactoring/ADMIN_REFACTOR_README.md)** - Admin refactor overview

#### Refactor Phases
- [Phase 1](./refactoring/REFACTOR_PHASE_1.md) - Immediate actions & quick wins
- [Phase 2](./refactoring/REFACTOR_PHASE_2.md) - Admin class split
- [Phase 3](./refactoring/REFACTOR_PHASE_3.md) - Live metrics implementation
- [Phase 4](./refactoring/REFACTOR_PHASE_4.md) - Testing and validation

#### Completed Phases
- [Phase 1 Completion](./refactoring/PHASE_1_COMPLETION.md)
- [Phase 2 Completion](./refactoring/PHASE_2_COMPLETION.md)
- [CSS Modularization Summary](./refactoring/CSS_MODULARIZATION_SUMMARY.md)

### Bug Fixes & Improvements

#### AI Summary 404 Fix
- **[Summary](./fixes/ai-summary-404-fix.md)** - Quick overview of the fix
- **[Flow Diagram](./fixes/ai-summary-404-fix-flow.md)** - Visual documentation with diagrams

#### Console Logging Enhancement
- **[Summary](./fixes/console-logging-fix.md)** - Quick overview
- **[Detailed Fix](./fixes/CONSOLE_LOGGING_FIX.md)** - Full implementation details
- **[Quick Start](./fixes/CONSOLE_LOGGING_QUICKSTART.md)** - Quick reference guide

#### Polling Conflict Resolution
- **[Summary](./fixes/polling-conflict-fix.md)** - Quick overview
- **[Detailed Fix](./fixes/POLLING_CONFLICT_FIX.md)** - Full implementation details
- **[Visual Guide](./fixes/POLLING_FIX_VISUAL_GUIDE.md)** - Diagrams and visual aids
- **[Quick Reference](./fixes/QUICK_REFERENCE_POLLING_FIX.md)** - Quick lookup guide

#### Second Viewing Fix
- **[Summary](./fixes/second-viewing-fix.md)** - Quick overview
- **[Detailed Fix](./fixes/SECOND_VIEWING_FIX.md)** - Full implementation details
- **[Before/After Comparison](./fixes/BEFORE_AFTER_COMPARISON.md)** - Comparison documentation

#### Selector Fix
- **[Documentation](./fixes/SELECTOR_FIX_DOCUMENTATION.md)** - Full documentation
- **[Summary](./fixes/SELECTOR_FIX_SUMMARY.md)** - Quick overview

#### Other Fixes
- **[Cache Removal](./fixes/CACHE_REMOVAL_SUMMARY.md)** - Cache removal implementation
- **[Duplicate Rendering Fix](./fixes/DUPLICATE_RENDERING_FIX.md)** - Market listings rendering issue resolution

### Technical Investigations

Comprehensive analyses of system behavior and design decisions:

- **[Investigations Directory](./investigations/README.md)** - Index of all technical investigations
- **[First Search Treatment Investigation](./investigations/FIRST_SEARCH_TREATMENT_INVESTIGATION.md)** - State management analysis (29KB)
- **[Investigation Summary](./investigations/INVESTIGATION_SUMMARY.md)** - Executive summary
- **[Action Summary](./investigations/ACTION_SUMMARY.md)** - Complete action summary

### Test Files

Manual test files and demonstrations:

- **[Tests Directory](./tests/README.md)** - Index of all test files
- HTML test files for manual testing and verification of fixes

---

## 📂 Directory Structure

```
docs/
├── README.md                          # This file - documentation index
├── replit.md                          # Development notes
├── architecture/                      # System architecture & analysis
│   ├── ARCHITECTURE.md
│   ├── ASSESSMENT.md
│   └── TESTING_GUIDE.md
├── refactoring/                       # Refactoring plans & completion reports
│   ├── REFACTOR_PLAN.md
│   ├── ADMIN_REFACTOR_PLAN.md
│   ├── ADMIN_REFACTOR_README.md
│   ├── REFACTOR_PHASE_1.md
│   ├── REFACTOR_PHASE_2.md
│   ├── REFACTOR_PHASE_3.md
│   ├── REFACTOR_PHASE_4.md
│   ├── PHASE_1_COMPLETION.md
│   ├── PHASE_2_COMPLETION.md
│   └── CSS_MODULARIZATION_SUMMARY.md
├── fixes/                             # Bug fixes & improvements
│   ├── ai-summary-404-fix.md
│   ├── ai-summary-404-fix-flow.md
│   ├── console-logging-fix.md
│   ├── CONSOLE_LOGGING_FIX.md
│   ├── CONSOLE_LOGGING_QUICKSTART.md
│   ├── polling-conflict-fix.md
│   ├── POLLING_CONFLICT_FIX.md
│   ├── POLLING_FIX_VISUAL_GUIDE.md
│   ├── QUICK_REFERENCE_POLLING_FIX.md
│   ├── second-viewing-fix.md
│   ├── SECOND_VIEWING_FIX.md
│   ├── BEFORE_AFTER_COMPARISON.md
│   ├── SELECTOR_FIX_DOCUMENTATION.md
│   ├── SELECTOR_FIX_SUMMARY.md
│   ├── CACHE_REMOVAL_SUMMARY.md
│   └── DUPLICATE_RENDERING_FIX.md
├── investigations/                    # Technical investigations & analyses
│   ├── README.md
│   ├── FIRST_SEARCH_TREATMENT_INVESTIGATION.md
│   ├── INVESTIGATION_SUMMARY.md
│   ├── ACTION_SUMMARY.md
│   ├── QUICK_REFERENCE.md
│   └── FIRST_SEARCH_VISUAL_SUMMARY.md
└── tests/                             # Test files & demonstrations
    ├── README.md
    ├── ai-summary-test.html
    ├── test-ai-summary-404-fix.html
    ├── test-second-viewing-console.html
    └── test-structured-errors.html
```

---

## 🔍 Finding What You Need

### Starting Out
1. **[Main README](../README.md)** - Plugin overview
2. **[Architecture](./architecture/ARCHITECTURE.md)** - System design
3. **[Assessment](./architecture/ASSESSMENT.md)** - Current state
4. **[Development Notes](./replit.md)** - Recent changes

### Working on Code
- Check **[fixes/](./fixes/)** directory for similar issues
- Each fix has a summary file for quick reference
- Detailed fix files contain full implementation details

### Refactoring
- **[Refactor Plan](./refactoring/REFACTOR_PLAN.md)** - Main roadmap
- Phase documents for specific areas
- Completion reports

### Testing
- **[Testing Guide](./architecture/TESTING_GUIDE.md)**
- Individual fix docs for test cases
- **[test files](./tests/)** for manual verification

### Investigations
- **[Investigations Directory](./investigations/)** - Technical analyses
- Investigation summaries
- Detailed implementation findings

---

## 📝 Documentation Conventions

### File Naming
- **Summaries**: `*-fix.md` or `*_SUMMARY.md` for quick overviews
- **Detailed docs**: Full caps for technical docs (e.g., `CONSOLE_LOGGING_FIX.md`)
- **Visual aids**: Include "VISUAL" or "FLOW" in name for diagram docs

### Structure
- Each fix has at least a summary file
- Complex fixes have multiple supporting docs
- Keep summaries concise (< 300 lines)
