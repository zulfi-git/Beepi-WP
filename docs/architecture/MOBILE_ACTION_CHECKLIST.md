# Mobile-First UI/UX - Action Checklist

> **Print this page and check off items as you complete them**

---

## 🚀 Quick Wins (Do Today - 4 hours)

### ⏱️ 1 Hour: Touch Targets
- [ ] Open `assets/css/responsive.css`
- [ ] Add mobile media query section
- [ ] Set all buttons to `min-height: 48px; min-width: 48px;`
- [ ] Set action boxes gap to `16px`
- [ ] Test: Tap all buttons on your phone - easy to hit?

### ⏱️ 2 Hours: ARIA Labels  
- [ ] Open `includes/class-vehicle-lookup-shortcode.php`
- [ ] Find action button rendering function
- [ ] Add `aria-label` to each button with description + price
- [ ] Example: `aria-label="Se eierinformasjon - Pris: 69 kroner"`
- [ ] Test: Turn on screen reader, can it describe buttons?

### ⏱️ 30 Minutes: Focus Indicators
- [ ] Open `assets/css/variables.css`
- [ ] Add `:focus-visible { outline: 3px solid #0066cc; }`
- [ ] Add mobile-specific larger outline (4px)
- [ ] Test: Tab through page, can you see focus?

### ⏱️ 15 Minutes: Input Font Size
- [ ] Open `assets/css/forms.css`
- [ ] Set inputs to `font-size: max(16px, 1rem);`
- [ ] Test: Focus input on iPhone, does it zoom?

**Total: 3h 45min**

---

## 📋 Week 1 Checklist

### Day 1: Touch & Focus
- [ ] Update touch target sizes
- [ ] Add focus indicators
- [ ] Test on iPhone
- [ ] Test on Android phone
- [ ] Document any issues

### Day 2: Accessibility
- [ ] Add ARIA labels to action buttons
- [ ] Add ARIA labels to status indicators
- [ ] Add live region for announcements
- [ ] Fix input font size
- [ ] Run axe accessibility test

### Day 3: Testing & Validation
- [ ] Test with VoiceOver (iOS)
- [ ] Test with TalkBack (Android)
- [ ] Test keyboard navigation
- [ ] Run Lighthouse mobile audit
- [ ] Fix any issues found

### Day 4: Documentation
- [ ] Record baseline metrics
- [ ] Document changes made
- [ ] Create before/after screenshots
- [ ] Update CHANGELOG

### Day 5: Review
- [ ] Team review of changes
- [ ] Get user feedback (if possible)
- [ ] Plan next phase
- [ ] Celebrate wins! 🎉

---

## 📋 Week 2 Checklist

### Fluid Typography Setup
- [ ] Open `assets/css/variables.css`
- [ ] Replace fixed font sizes with `clamp()` values
- [ ] `--font-size-base: clamp(1rem, 0.9rem + 0.5vw, 1.125rem);`
- [ ] `--font-size-lg: clamp(1.125rem, 1rem + 0.6vw, 1.5rem);`
- [ ] `--font-size-xl: clamp(1.5rem, 1.2rem + 1.5vw, 2rem);`
- [ ] Test at 320px, 375px, 768px, 1920px

### Fluid Spacing Setup
- [ ] Add fluid spacing variables
- [ ] `--space-md: clamp(1rem, 0.8rem + 1vw, 1.5rem);`
- [ ] `--space-lg: clamp(1.5rem, 1.2rem + 1.5vw, 2.5rem);`
- [ ] Update components to use variables
- [ ] Test at multiple screen sizes

### Component Updates
- [ ] Update `buttons.css` to use fluid variables
- [ ] Update `results.css` to use fluid variables
- [ ] Update `responsive.css` to use fluid variables
- [ ] Visual regression test
- [ ] Fix any layout breaks

---

## 📋 Week 3-4 Checklist

### Sticky Bottom Bar (Mobile)
- [ ] Create sticky action bar CSS
- [ ] Position at bottom with safe-area-inset
- [ ] Move primary CTA into bar
- [ ] Test scrolling behavior
- [ ] Test on notched phones

### Progressive Disclosure
- [ ] Identify sections to make expandable
- [ ] Use native `<details>` element
- [ ] Style summary as clickable (48px min)
- [ ] Add arrow indicator
- [ ] Test expand/collapse on mobile

### Safe Area Insets
- [ ] Add viewport-fit=cover to meta tag
- [ ] Add safe-area-inset support to sticky elements
- [ ] Test on iPhone with notch
- [ ] Test on Android with punch-hole

### Enhanced Action Cards
- [ ] Redesign action card layout
- [ ] Add clear value proposition
- [ ] Add price display
- [ ] Add CTA button
- [ ] Test on mobile and desktop

---

## ✅ Testing Checklist

### Device Testing
- [ ] iPhone SE (smallest iOS)
- [ ] iPhone 13/14 (standard iOS)
- [ ] iPhone Pro Max (largest iOS)
- [ ] Samsung Galaxy (Android)
- [ ] iPad (tablet)

### Functional Testing
- [ ] Can tap all buttons easily
- [ ] Text is readable without zooming
- [ ] No horizontal scrolling
- [ ] Forms work without zoom
- [ ] Sticky bar doesn't cover content
- [ ] Expandable sections work

### Accessibility Testing
- [ ] VoiceOver (iOS) test
- [ ] TalkBack (Android) test
- [ ] Keyboard navigation test
- [ ] Focus indicators visible
- [ ] Color contrast check
- [ ] ARIA labels present

### Performance Testing
- [ ] Lighthouse mobile score: ___ (target: 90+)
- [ ] Accessibility score: ___ (target: 95+)
- [ ] Performance score: ___ (target: 85+)
- [ ] LCP: ___ (target: < 2.5s)
- [ ] CLS: ___ (target: < 0.1)

---

## 📊 Metrics Tracking

### Baseline (Before Changes)
- [ ] Mobile bounce rate: ___%
- [ ] Mobile conversion rate: ___%
- [ ] Mobile time on page: ___s
- [ ] Accessibility score: ___
- [ ] Performance score: ___
- [ ] Date recorded: _______

### After Quick Wins (Week 1)
- [ ] Mobile bounce rate: ___% (change: ___%)
- [ ] Mobile conversion rate: ___% (change: ___%)
- [ ] Mobile time on page: ___s (change: ___s)
- [ ] Accessibility score: ___ (change: ___)
- [ ] Performance score: ___ (change: ___)
- [ ] Date recorded: _______

### After Typography (Week 2)
- [ ] Mobile bounce rate: ___% (change: ___%)
- [ ] Mobile conversion rate: ___% (change: ___%)
- [ ] Mobile time on page: ___s (change: ___s)
- [ ] User satisfaction: ___/5
- [ ] Date recorded: _______

### After Full Implementation (Week 4)
- [ ] Mobile bounce rate: ___% (change: ___%)
- [ ] Mobile conversion rate: ___% (change: ___%)
- [ ] Mobile time on page: ___s (change: ___s)
- [ ] User satisfaction: ___/5
- [ ] Date recorded: _______

---

## 🎯 Success Criteria

### Must Achieve (Required)
- [ ] All touch targets ≥ 48px
- [ ] All interactive elements have ARIA labels
- [ ] Focus indicators visible on all elements
- [ ] No iOS zoom on input focus
- [ ] Accessibility score ≥ 95

### Should Achieve (Target)
- [ ] Mobile bounce rate reduced by 15%
- [ ] Mobile conversion increased by 10%
- [ ] User satisfaction ≥ 4.5/5
- [ ] Performance score ≥ 85

### Nice to Achieve (Stretch)
- [ ] Mobile conversion increased by 25%
- [ ] Lighthouse mobile score ≥ 90
- [ ] Zero accessibility issues
- [ ] 5/5 user satisfaction

---

## 📁 Files to Update

### Critical Files (Must Change)
- [ ] `assets/css/responsive.css` - Touch targets, mobile layout
- [ ] `assets/css/variables.css` - Fluid typography, spacing, focus
- [ ] `assets/css/forms.css` - Input font size
- [ ] `includes/class-vehicle-lookup-shortcode.php` - ARIA labels

### Important Files (Should Change)
- [ ] `assets/css/buttons.css` - Action button styling
- [ ] `assets/css/results.css` - Results layout
- [ ] `assets/js/vehicle-lookup.js` - Mobile interactions

### Supporting Files (Nice to Change)
- [ ] `assets/css/market.css` - Market listings
- [ ] `assets/css/ai-summary.css` - AI summary section

---

## 💾 Backup & Safety

### Before Starting
- [ ] Take full database backup
- [ ] Take full file backup
- [ ] Create git branch for changes
- [ ] Document current site behavior
- [ ] Record baseline metrics

### During Implementation
- [ ] Commit after each major change
- [ ] Test after each commit
- [ ] Document any issues
- [ ] Keep rollback plan ready

### After Completion
- [ ] Full site testing
- [ ] User acceptance testing
- [ ] Monitor error logs
- [ ] Watch analytics for 48 hours
- [ ] Get user feedback

---

## 🆘 Rollback Plan

### If Issues Found
1. [ ] Document the issue
2. [ ] Take screenshot of problem
3. [ ] Check git diff for recent changes
4. [ ] Revert problematic change
5. [ ] Test that issue is resolved
6. [ ] Plan fix for next iteration

### Emergency Rollback
1. [ ] `git revert HEAD` (undo last commit)
2. [ ] `git push origin main --force`
3. [ ] Clear all caches
4. [ ] Test site functionality
5. [ ] Document what went wrong
6. [ ] Plan alternative approach

---

## 📚 Reference Documents

- [ ] Read: [Mobile-First Summary](./MOBILE_FIRST_FEEDBACK_SUMMARY.md)
- [ ] Review: [Production Feedback](./PRODUCTION_UI_FEEDBACK.md)
- [ ] Check: [Quick Reference](./MOBILE_UI_QUICK_REFERENCE.md)
- [ ] Study: [mobile-first-design.html](../tests/ui-examples/mobile-first-design.html)
- [ ] Reference: [Implementation Guide](./UI_UX_IMPLEMENTATION_GUIDE.md)

---

## 👥 Team Communication

### Before Starting
- [ ] Notify team of upcoming changes
- [ ] Share implementation plan
- [ ] Schedule review meetings
- [ ] Set up communication channel

### During Implementation
- [ ] Daily standup updates
- [ ] Share progress screenshots
- [ ] Report any blockers
- [ ] Ask for help when needed

### After Completion
- [ ] Demo the changes
- [ ] Share metrics/results
- [ ] Gather feedback
- [ ] Celebrate success!

---

## Notes & Issues

Use this space to track issues, ideas, or notes during implementation:

```
Date: ________  Issue: ___________________________________
Resolution: _____________________________________________

Date: ________  Issue: ___________________________________
Resolution: _____________________________________________

Date: ________  Issue: ___________________________________
Resolution: _____________________________________________

Date: ________  Idea: ___________________________________
Action: _________________________________________________

Date: ________  Feedback: _______________________________
Action: _________________________________________________
```

---

**Started:** _______ / _______ / _______  
**Completed:** _______ / _______ / _______  
**Total Time:** _______ hours  
**Result:** 🎉 Success! / ⚠️ Needs work / ❌ Rolled back

---

**This is a living document - update it as you progress!**

Print date: _______________________  
Version: 1.0
