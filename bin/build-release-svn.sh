#!/usr/bin/bash

rsync \
	-av \
	--exclude ".git" \
	--exclude "bin" \
	--exclude "vendor" \
	--exclude "node_modules" \
	--exclude "releases" \
	--exclude "composer.*" \
	--exclude ".*" \
	--exclude "package*.json" \
	--exclude "phpcs*" \
	. releases/svn/trunk

rsync \
	-av \
	assets/screenshot* releases/svn/assets
