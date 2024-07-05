# INTRODUCTION

Group Membership Request - This module extends [group](https://www.drupal.org/project/group) module and allows user to request a membership to the group.

# REQUIREMENTS

This module requires the following modules:

* [Group](https://www.drupal.org/project/group)
* [State machine](https://www.drupal.org/project/state_machine)

# INSTALLATION

You can install this module using standard user interface or using cli command

```
drush en grequest
```

[further information](https://www.drupal.org/node/1897420)

# CONFIGURATION

 1) Install Group relationship type "**Group Membership Request**"

/admin/group/types/manage/[ YOUR_GROUP_TYPE ]/content

 2) Provide permission "**Request group membership**" to the outsider role

/admin/group/types/manage/[ YOUR_GROUP_TYPE ]/permissions

 3) Users who are not members of the group should see a link in the
    group operations block to request group membership

 4) Check /group/[ GROUP ID ]/members-pending to see, to approve or
    to reject all pending membership requests. To access this page
    the current user need to have "**administer membership requests**"
    permission.


#  Development

Request manager helps to handle main operation on requests.
Please use it whenever you need to create, approve or reject a request.
It will trigger all necessary events for you.

```
$request_manager = \Drupal::service('grequest.membership_request_manager');
```

1) To add a new request in the code use
```
$request_manager->create($group, $user);
```

2) To approve a new request in the code use
```
$request_manager->approve($group_relationship_request_membership);
```

3) To reject a new request in the code use
```
$request_manager->reject($group_relationship_request_membership);
```

4) To get the request fot the user

```
$request_manager->getMembershipRequest($user, $group);
```

# Available events

#### For request's creation

```
group_membership_request.create.pre_transition

group_membership_request.create.post_transition
```

#### For request's approval

```
group_membership_request.approve.pre_transition

group_membership_request.approve.post_transition
```

#### For request's rejection

```
group_membership_request.reject.pre_transition

group_membership_request.reject.post_transition
```

#### An example of an event subscriber

```
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class MyEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'group_membership_request.create.pre_transition' => 'preCreate',
      'group_membership_request.create.post_transition' => 'postCreate',
      'group_membership_request.approve.pre_transition' => 'preApprove',
      'group_membership_request.approve.post_transition' => 'postApprove',
      'group_membership_request.reject.pre_transition' => 'preReject',
      'group_membership_request.reject.post_transition' => 'postReject',
    ];
  }

  public function preCreate(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postCreate(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function preApprove(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postApprove(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function preReject(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postReject(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  protected function setMessage(WorkflowTransitionEvent $event, $phase) {
    $str = '@entity_label (@field_name)';
    $str .= '- @state_label at @phase (workflow: @workflow).';
    \Drupal::messenger()->addMessage(new TranslatableMarkup($str, [
      '@entity_label' => $event->getEntity()->label(),
      '@field_name' => $event->getFieldName(),
      '@state_label' => $event->getTransition()->getToState()->getLabel(),
      '@workflow' => $event->getWorkflow()->getId(),
      '@phase' => $phase,
    ]));
  }

}

```

# Maintainers

[Nikolay Lobachev](https://www.drupal.org/u/lobsterr)
