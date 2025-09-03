#!/usr/bin/env node

/**
 * Comprehensive Test Runner for Frontend Testing Suite
 * 
 * This script runs all test suites and generates a comprehensive report
 */

import { execSync } from 'child_process'
import fs from 'fs'
import path from 'path'

const TEST_CATEGORIES = {
  unit: {
    name: 'Unit Tests',
    pattern: 'tests/js/Components/**/*.test.js',
    description: 'Individual component unit tests'
  },
  integration: {
    name: 'Integration Tests', 
    pattern: 'tests/js/Integration/**/*.test.js',
    description: 'Component integration and workflow tests'
  },
  e2e: {
    name: 'End-to-End Tests',
    pattern: 'tests/js/E2E/**/*.test.js', 
    description: 'Complete user workflow tests'
  },
  mobile: {
    name: 'Mobile Tests',
    pattern: 'tests/js/Mobile/**/*.test.js',
    description: 'Mobile device and touch interaction tests'
  },
  performance: {
    name: 'Performance Tests',
    pattern: 'tests/js/Performance/**/*.test.js',
    description: 'Performance and load testing'
  }
}

const runTestCategory = (category, pattern) => {
  console.log(`\n🧪 Running ${category}...`)
  console.log(`Pattern: ${pattern}`)
  
  try {
    const result = execSync(`npx vitest run "${pattern}" --reporter=verbose`, {
      encoding: 'utf8',
      stdio: 'pipe'
    })
    
    console.log(`✅ ${category} completed successfully`)
    return { success: true, output: result }
  } catch (error) {
    console.log(`❌ ${category} failed`)
    console.error(error.stdout || error.message)
    return { success: false, output: error.stdout || error.message }
  }
}

const generateTestReport = (results) => {
  const report = {
    timestamp: new Date().toISOString(),
    summary: {
      total: Object.keys(results).length,
      passed: Object.values(results).filter(r => r.success).length,
      failed: Object.values(results).filter(r => !r.success).length
    },
    categories: results
  }
  
  const reportPath = path.join(process.cwd(), 'test-results', 'comprehensive-test-report.json')
  
  // Ensure directory exists
  fs.mkdirSync(path.dirname(reportPath), { recursive: true })
  
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2))
  
  console.log(`\n📊 Test report generated: ${reportPath}`)
  return report
}

const printSummary = (report) => {
  console.log('\n' + '='.repeat(60))
  console.log('🎯 COMPREHENSIVE TEST SUITE SUMMARY')
  console.log('='.repeat(60))
  
  console.log(`\n📈 Overall Results:`)
  console.log(`   Total Categories: ${report.summary.total}`)
  console.log(`   Passed: ${report.summary.passed} ✅`)
  console.log(`   Failed: ${report.summary.failed} ❌`)
  console.log(`   Success Rate: ${((report.summary.passed / report.summary.total) * 100).toFixed(1)}%`)
  
  console.log(`\n📋 Category Breakdown:`)
  Object.entries(report.categories).forEach(([category, result]) => {
    const status = result.success ? '✅' : '❌'
    const info = TEST_CATEGORIES[category]
    console.log(`   ${status} ${info.name}: ${info.description}`)
  })
  
  if (report.summary.failed > 0) {
    console.log(`\n⚠️  Some tests failed. Check the detailed output above.`)
    process.exit(1)
  } else {
    console.log(`\n🎉 All test categories passed successfully!`)
  }
}

const main = async () => {
  console.log('🚀 Starting Comprehensive Frontend Test Suite')
  console.log('This will run all unit, integration, E2E, mobile, and performance tests')
  
  const results = {}
  
  // Run each test category
  for (const [key, config] of Object.entries(TEST_CATEGORIES)) {
    results[key] = runTestCategory(config.name, config.pattern)
  }
  
  // Generate and display report
  const report = generateTestReport(results)
  printSummary(report)
}

// Run if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
  main().catch(console.error)
}

export { runTestCategory, generateTestReport, TEST_CATEGORIES }