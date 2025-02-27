# Done to make sure the crawler queue is empty, so that we will only crawl important pages.
$ vendor/bin/typo3 crawler:flushQueue all

# Now we want to fill the crawler queue,
# This will start on page uid 1 with the deployment configuration and depth 99,
# --mode exec crawles the pages instantly so we don't need a secondary process for that.
$ vendor/bin/typo3 crawler:buildQueue 1 deployment --depth 99 --mode exec

# Add the rest of the pages to crawler queue and have the processed with the scheduler
# --mode queue is default, but it is  added for visibility,
# we assume that you have a crawler configuration called default
$ vendor/bin/typo3 crawler:buildQueue 1 default --depth 99 --mode queue
