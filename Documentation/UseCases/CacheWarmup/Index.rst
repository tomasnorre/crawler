..  include:: /Includes.rst.txt

..  _use-case-cache-warm-up:

=============
Cache warm up
=============

To have a website that is fast for the end-user is essential, therefore having a
warm cache even before the first user hits the newly deployed website, will be
beneficial, so how could one achieve this?

The crawler have some command line tools (hereafter cli tools) that can be used,
during deployments. The cli tools is implemented with the `symfony/console`
which have been standard in TYPO3 for a while.

There are 3 commands that can be of you benefit during deployments.

* :bash:`vendor/bin/typo3 crawler:flushQueue`
* :bash:`vendor/bin/typo3 crawler:buildQueue`
* :bash:`vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low`

You can see more on which parameters they take in :ref:`command-controller`,
this example will provide suggestion on how you can set it up, and you can
adjust with additional parameters if you like.

..  rst-class:: bignums-xxl

..  _create-crawler-configuration:
#.  Create crawler configuration

    First we need a `crawler configuration` these are stored in the database. You
    can add it via the backend, see :ref:`backend-configuration-record`.

    It's suggested to select the most important pages of the website and add
    them to a Crawler configuration called e.g. `deployment`:

    ..  figure:: /Images/backend_configuration_deployment.png
        :alt: Crawler configuration record

        Crawler configuration record

    ..  hint::
        Let's say your website has frontend users with one or multiple user
        groups. In this case you need to create multiple crawler
        configurations: For every possible combination of User groups that a
        user can have you need to create a individual crawler configuration.

        All those crawler configurations need to be added to the
        `crawler:buildQueue` command to be considered. If you miss this
        some user get a warmed up cache but those with a combination of
        user groups which was not taken into account in a crawler configuration
        will get an uncached page.

#.  Build the queue

    With this only pages added will be crawled when using this configuration. So
    how will we execute this from CLI during deployment? I don't know which
    deployment tool you use, but it's not important as long as you can execute
    shell commands. What would you need to execute?

    ..  literalinclude:: _commands.bash
        :language: bash

#.  Process the queue

    The last step will add the pages to the queue. They are processed by a
    worker consuming the crawler transports, see :ref:`with-crown`:

    ..  code-block:: bash

        vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low


With there steps you will have a website that is faster by the first visit after
a deployment, and the rest of the website is crawled automatically shortly
after.

`#HappyCrawling`
