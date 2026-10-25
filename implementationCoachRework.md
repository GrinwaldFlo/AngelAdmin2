# Coach access rework

We have members, that can only view and edit their informations.
And coaches that should be able to manage their teams.

For now to identify the coach there is:
- The field `members.coach`
- A role "coach" exists
- A group "coach" exists

## Purpose

Allows to assign for each group, multiple coach and help-coach.
Narrows access to only their group and don't show groups they are not coach or help-coach

## Required

- Remove field `members.coach`
- We will remove the group Coach that should not be usefull anymore
- It certainly make sense to still have a role "coach" (help-coach and coach will have the same role)
- Fix the visibility foreach view 

## Interface

- Rework all pages using the coach information with this new properties
- Have a dedicated page where we have the list of all active teams so we can add coach and help-coach