flightzilla
=====

Idea
-----

flightzilla will assist in an environment where it is not possible to use enterprise ticket-management-software, but bugzilla alone is not more suitable.

Usage:
-----

flightzilla is based on zend-framework 2 and many open-source tools & libraries like jquery, twitter-bootstrap, etc.. flightzilla can use mergy (https://github.com/hpbuniat/mergy), to execute simple merges.

To install flightzilla execute:

    git clone
    php composer.phar install
    cp ./module/Flightzilla/config/flightzilla.local.php.dist ./config/autoload/flightzilla.local.php
    cp ./vendor/zendframework/zend-developer-tools/config/zenddevelopertools.local.php.dist ./config/autoload/zenddevelopertools.local.php

To update the dependencies run:

    php composer.phar update
    bower update
    bower-installer

Features:
-----

flightzilla offers a variety of features, which are split into several categories. All list-views do provide shorthands to modify the tickets, open them within bugzilla or create a print-view to print snippets for your "physical" Kanban-Board.

- Personal Dashboard
    - My Dashboard
        - Shows Tickets which are assigned to you (separated in sprints or all tickets at once)
        - The dashboard includes some handy shortcuts for common workflows (e.g. setting a ticket to "assigned" or "resolved")
        - Includes several task-lists:
            - Tickets with deadlines
            - Unanswered Comment-Requests
            - Tickets with failed testings
    - My Tickets (A list of your tickets - comparable to the "My Bugs"-List)
    - Conflicts - Several listings to check the integrity of ticket-status:
        - RESOLVED-Tickets must not have only failed- or rejected testings
        - Tickets without estimation (Organization) must have a due-date
        - Tickets with worked-time must have a estimation
        - Tickets should not be untouched for a certain amount of time (depending on your configuration)
        - Flags should not be unanswered for a certain amount of time (depending on your configuration)
        - and many more, waiting for your rules
- Kanban Board (A kanban-style-board, based on the current ticket-status)
- Tickets (A list of all tickets, grouped by workflow-status & assigned project)
    - Provides a very detailed overview over all tickets - the status is indicated by many icons & colors
    - Quick & Easy filtering, sorting, ..
    - Allows to modify tickets within predefined workflows (e.g. request testing)
    - Create Release-Log-Entries
- Team
    - Dashboard (Similar to the "My Dashboard"-View, but including all configured team-members)
    - Ticket-List (A list of all tickets, grouped by their assignee)
    - Weekly-Sprint (A list of tickets, planned for the next sprint)
    - Summary (Beta!)
    - Review (WIP!, Gantt-View of worked hours per resource)
- Project
    - List (WIP!, A list of projects, visualized like a dashboard)
    - Board (A kanban-style board, with tickets grouped to a project)
    - Ticket-List (List-View of Tickets, sorted to their projects)
    - Planning (WIP!, Gantt-View of projects)
    - Planning (Detailed) (WIP!, Gantt-View of projects, inkluding the tickets)
    - Resource (WIP!, Gantt-View of resources)
    - Graph (WIP!, Show projects in a graph to identify stars, cash cows & dogs)
- Mergy (Access to mergy directly from flightzilla. Mergy is a tool to execute merges)
- Analytics
    - Campaigns (Access Google-Analytics Campaign-Data)
    - Browser (Access Google-Analytics Browser-Statistics)
