/**
 * Mobile Responsiveness Audit Utility
 * Provides tools to audit and fix mobile responsiveness issues
 */

class MobileAudit {
  constructor() {
    this.issues = [];
    this.recommendations = [];
    this.deviceBreakpoints = {
      mobile: 640,
      tablet: 768,
      desktop: 1024,
      xl: 1280
    };
  }

  // Audit component for mobile responsiveness
  auditComponent(element, componentName = 'Unknown') {
    const issues = [];
    const rect = element.getBoundingClientRect();
    const styles = window.getComputedStyle(element);

    // Check for minimum touch target size (44px recommended)
    const touchTargets = element.querySelectorAll('button, a, input, select, textarea');
    touchTargets.forEach((target, index) => {
      const targetRect = target.getBoundingClientRect();
      if (targetRect.width < 44 || targetRect.height < 44) {
        issues.push({
          type: 'touch-target-size',
          severity: 'high',
          element: target,
          message: `Touch target ${index} is too small (${targetRect.width}x${targetRect.height}px). Minimum recommended: 44x44px`,
          recommendation: 'Add padding or increase button size for better touch accessibility'
        });
      }
    });

    // Check for horizontal scrolling
    if (element.scrollWidth > element.clientWidth) {
      issues.push({
        type: 'horizontal-scroll',
        severity: 'high',
        element: element,
        message: 'Element causes horizontal scrolling on mobile',
        recommendation: 'Use responsive design patterns like flexbox or grid with proper wrapping'
      });
    }

    // Check for fixed widths that might break on mobile
    const fixedWidthElements = element.querySelectorAll('*');
    fixedWidthElements.forEach((el) => {
      const elStyles = window.getComputedStyle(el);
      const width = elStyles.width;
      
      if (width && width.includes('px') && !width.includes('%') && !width.includes('auto')) {
        const widthValue = parseInt(width);
        if (widthValue > this.deviceBreakpoints.mobile) {
          issues.push({
            type: 'fixed-width',
            severity: 'medium',
            element: el,
            message: `Element has fixed width (${width}) that may not work on mobile`,
            recommendation: 'Use responsive units like %, vw, or max-width with media queries'
          });
        }
      }
    });

    // Check for text that might be too small on mobile
    const textElements = element.querySelectorAll('p, span, div, h1, h2, h3, h4, h5, h6');
    textElements.forEach((textEl) => {
      const textStyles = window.getComputedStyle(textEl);
      const fontSize = parseFloat(textStyles.fontSize);
      
      if (fontSize < 14) {
        issues.push({
          type: 'small-text',
          severity: 'medium',
          element: textEl,
          message: `Text size (${fontSize}px) may be too small for mobile`,
          recommendation: 'Use minimum 14px font size for mobile readability'
        });
      }
    });

    // Check for elements that might need touch-friendly spacing
    const interactiveElements = element.querySelectorAll('button, a, input, select');
    interactiveElements.forEach((el, index) => {
      const nextEl = interactiveElements[index + 1];
      if (nextEl) {
        const elRect = el.getBoundingClientRect();
        const nextRect = nextEl.getBoundingClientRect();
        const distance = Math.abs(nextRect.top - elRect.bottom);
        
        if (distance < 8) {
          issues.push({
            type: 'insufficient-spacing',
            severity: 'medium',
            element: el,
            message: `Insufficient spacing (${distance}px) between interactive elements`,
            recommendation: 'Add at least 8px spacing between touch targets'
          });
        }
      }
    });

    // Check for images without responsive attributes
    const images = element.querySelectorAll('img');
    images.forEach((img) => {
      const imgStyles = window.getComputedStyle(img);
      if (!img.hasAttribute('srcset') && imgStyles.maxWidth !== '100%') {
        issues.push({
          type: 'non-responsive-image',
          severity: 'low',
          element: img,
          message: 'Image may not be responsive',
          recommendation: 'Add max-width: 100% and height: auto, or use srcset for different screen sizes'
        });
      }
    });

    return {
      componentName,
      issues,
      score: this.calculateMobileScore(issues),
      recommendations: this.generateRecommendations(issues)
    };
  }

  // Calculate mobile responsiveness score (0-100)
  calculateMobileScore(issues) {
    let score = 100;
    
    issues.forEach((issue) => {
      switch (issue.severity) {
        case 'high':
          score -= 15;
          break;
        case 'medium':
          score -= 8;
          break;
        case 'low':
          score -= 3;
          break;
      }
    });

    return Math.max(0, score);
  }

  // Generate recommendations based on issues
  generateRecommendations(issues) {
    const recommendations = [];
    const issueTypes = [...new Set(issues.map(issue => issue.type))];

    issueTypes.forEach((type) => {
      switch (type) {
        case 'touch-target-size':
          recommendations.push({
            priority: 'high',
            action: 'Increase touch target sizes',
            description: 'Ensure all interactive elements are at least 44x44px',
            implementation: 'Add min-height: 44px; min-width: 44px; to buttons and links'
          });
          break;
        case 'horizontal-scroll':
          recommendations.push({
            priority: 'high',
            action: 'Fix horizontal scrolling',
            description: 'Prevent horizontal overflow on mobile devices',
            implementation: 'Use overflow-x: hidden; or responsive design patterns'
          });
          break;
        case 'fixed-width':
          recommendations.push({
            priority: 'medium',
            action: 'Replace fixed widths with responsive units',
            description: 'Use percentage, viewport units, or max-width instead of fixed pixels',
            implementation: 'Replace width: Xpx with max-width: X% or width: 100%'
          });
          break;
        case 'small-text':
          recommendations.push({
            priority: 'medium',
            action: 'Increase text size for mobile',
            description: 'Ensure text is readable on small screens',
            implementation: 'Use media queries to increase font-size on mobile'
          });
          break;
        case 'insufficient-spacing':
          recommendations.push({
            priority: 'medium',
            action: 'Add spacing between interactive elements',
            description: 'Prevent accidental touches by adding adequate spacing',
            implementation: 'Add margin or padding between touch targets'
          });
          break;
        case 'non-responsive-image':
          recommendations.push({
            priority: 'low',
            action: 'Make images responsive',
            description: 'Ensure images scale properly on different screen sizes',
            implementation: 'Add max-width: 100%; height: auto; to images'
          });
          break;
      }
    });

    return recommendations;
  }

  // Audit entire page
  auditPage() {
    const components = document.querySelectorAll('[data-component]');
    const results = [];

    components.forEach((component) => {
      const componentName = component.getAttribute('data-component');
      const audit = this.auditComponent(component, componentName);
      results.push(audit);
    });

    // Also audit common Vue components
    const vueComponents = document.querySelectorAll('[data-v-]');
    vueComponents.forEach((component) => {
      const componentName = component.className || 'Vue Component';
      const audit = this.auditComponent(component, componentName);
      results.push(audit);
    });

    return {
      results,
      overallScore: this.calculateOverallScore(results),
      summary: this.generateSummary(results)
    };
  }

  // Calculate overall page score
  calculateOverallScore(results) {
    if (results.length === 0) return 100;
    
    const totalScore = results.reduce((sum, result) => sum + result.score, 0);
    return Math.round(totalScore / results.length);
  }

  // Generate audit summary
  generateSummary(results) {
    const totalIssues = results.reduce((sum, result) => sum + result.issues.length, 0);
    const highPriorityIssues = results.reduce((sum, result) => {
      return sum + result.issues.filter(issue => issue.severity === 'high').length;
    }, 0);

    return {
      totalComponents: results.length,
      totalIssues,
      highPriorityIssues,
      averageScore: this.calculateOverallScore(results),
      needsImprovement: results.filter(result => result.score < 80).length
    };
  }

  // Test touch interactions
  testTouchInteractions(element) {
    const touchTargets = element.querySelectorAll('button, a, input, select, textarea');
    const results = [];

    touchTargets.forEach((target) => {
      const rect = target.getBoundingClientRect();
      const isAccessible = rect.width >= 44 && rect.height >= 44;
      const hasProperSpacing = this.checkSpacing(target);
      
      results.push({
        element: target,
        isAccessible,
        hasProperSpacing,
        size: { width: rect.width, height: rect.height },
        recommendations: isAccessible ? [] : ['Increase touch target size to at least 44x44px']
      });
    });

    return results;
  }

  // Check spacing around element
  checkSpacing(element) {
    const rect = element.getBoundingClientRect();
    const siblings = Array.from(element.parentElement?.children || []);
    const elementIndex = siblings.indexOf(element);
    
    let hasAdequateSpacing = true;
    
    // Check previous sibling
    if (elementIndex > 0) {
      const prevSibling = siblings[elementIndex - 1];
      const prevRect = prevSibling.getBoundingClientRect();
      const distance = rect.top - prevRect.bottom;
      if (distance < 8) hasAdequateSpacing = false;
    }
    
    // Check next sibling
    if (elementIndex < siblings.length - 1) {
      const nextSibling = siblings[elementIndex + 1];
      const nextRect = nextSibling.getBoundingClientRect();
      const distance = nextRect.top - rect.bottom;
      if (distance < 8) hasAdequateSpacing = false;
    }
    
    return hasAdequateSpacing;
  }

  // Generate CSS fixes for common issues
  generateCSSFixes(issues) {
    const fixes = [];
    
    issues.forEach((issue) => {
      switch (issue.type) {
        case 'touch-target-size':
          fixes.push(`
/* Fix touch target size */
button, a, input, select, textarea {
  min-height: 44px;
  min-width: 44px;
  padding: 8px 12px;
}
          `);
          break;
        case 'horizontal-scroll':
          fixes.push(`
/* Prevent horizontal scroll */
* {
  box-sizing: border-box;
}
.container {
  max-width: 100%;
  overflow-x: hidden;
}
          `);
          break;
        case 'small-text':
          fixes.push(`
/* Improve text readability on mobile */
@media (max-width: 640px) {
  body {
    font-size: 16px;
    line-height: 1.5;
  }
  p, span, div {
    font-size: 14px;
  }
}
          `);
          break;
        case 'insufficient-spacing':
          fixes.push(`
/* Add spacing between interactive elements */
button + button,
a + a,
input + input {
  margin-top: 8px;
}
@media (max-width: 640px) {
  .button-group > * {
    margin-bottom: 8px;
  }
}
          `);
          break;
      }
    });
    
    return [...new Set(fixes)]; // Remove duplicates
  }

  // Export audit report
  exportReport(results) {
    const report = {
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight
      },
      results,
      summary: this.generateSummary(results),
      recommendations: this.generateRecommendations(
        results.flatMap(result => result.issues)
      ),
      cssfixes: this.generateCSSFixes(
        results.flatMap(result => result.issues)
      )
    };

    return report;
  }
}

// Device detection utilities
export const DeviceUtils = {
  isMobile() {
    return window.innerWidth <= 640;
  },

  isTablet() {
    return window.innerWidth > 640 && window.innerWidth <= 1024;
  },

  isDesktop() {
    return window.innerWidth > 1024;
  },

  isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  },

  getDeviceType() {
    if (this.isMobile()) return 'mobile';
    if (this.isTablet()) return 'tablet';
    return 'desktop';
  },

  getViewportSize() {
    return {
      width: window.innerWidth,
      height: window.innerHeight
    };
  },

  // Check if device supports hover
  supportsHover() {
    return window.matchMedia('(hover: hover)').matches;
  },

  // Get device pixel ratio
  getPixelRatio() {
    return window.devicePixelRatio || 1;
  },

  // Check if device is in landscape mode
  isLandscape() {
    return window.innerWidth > window.innerHeight;
  },

  // Get safe area insets for devices with notches
  getSafeAreaInsets() {
    const style = getComputedStyle(document.documentElement);
    return {
      top: style.getPropertyValue('env(safe-area-inset-top)') || '0px',
      right: style.getPropertyValue('env(safe-area-inset-right)') || '0px',
      bottom: style.getPropertyValue('env(safe-area-inset-bottom)') || '0px',
      left: style.getPropertyValue('env(safe-area-inset-left)') || '0px'
    };
  }
};

export default MobileAudit;