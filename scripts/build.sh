#!/bin/bash

echo "🚀 Starting build FE assets process..."

# Ensure we're on main and up to date
git checkout main
git pull origin main

# Switch to production
git checkout production-dreamhost

# Merge main into production
echo "📦 Merging main into production-dreamhost..."
git merge main

# Build frontend
echo "🔨 Building frontend assets..."
npm run build

# Commit build files
echo "💾 Committing build files..."
git add public/build
git commit -m "Deploy: $(date +%Y-%m-%d_%H:%M:%S)"

# Push to GitHub (backup)
echo "📤 Pushing up to GitHub..."
git push origin production-dreamhost

# Deploy to DreamHost
echo "🚢 Pushing to DreamHost..."
git push dreamhost production-dreamhost

# Switch back to main
git checkout main

echo "✅ Process complete!"
