<?php  namespace Bogardo\Mailgun\Mailgun\Lists;


use Bogardo\Mailgun\Mailgun\MailgunApi;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Collection;

class Mailinglist extends MailgunApi {

    public $name;

    public $address;

    public $description;

    public $members_count;

    public $access_level;

    protected $created_at;

    /**
     * @param string $address
     */
    public function __construct($address = "")
    {
        if ($address) {
            $this->address = $address;
        }
    }

    /**
     * @param $list
     *
     * @return $this
     */
    public function setList($list)
    {
        $this->name = $list->name;
        $this->address = $list->address;
        $this->description = $list->description;
        $this->members_count = $list->members_count;
        $this->access_level = $list->access_level;
        $this->created_at = $this->parseDate($list->created_at)->format('Y-m-d H:i:s');
        unset($this->mailgun);

        return $this;
    }

    /**
     * Update this mailinglist
     *
     * @param array $params
     *
     * @return $this
     */
    public function update($params = [])
    {
        $this->setList($this->mailgun()->put("lists/{$this->address}", $params)->http_response_body->list);
        return $this;
    }

    /**
     * Delete this mailinglist
     *
     * @return bool
     */
    public function delete()
    {
        $this->mailgun()->delete("lists/{$this->address}");
        return true;
    }

    /**
     * Get all members
     *
     * @param array $params
     *
     * @return Collection
     */
    public function members($params = [])
    {
        $items = new Collection([]);

        $result = $this->mailgun()->get("lists/{$this->address}/members", $this->_parseParams($params))->http_response_body;

        if ($result) {
            foreach ($result->items as $item) {
                $member = new Member();
                $member->setMember($item);
                $items->push($member);
            }
        }

        return $items;
    }

    /**
     * Get a single member
     *
     * @param $memberaddress
     *
     * @return Member
     */
    public function member($memberaddress)
    {
        $result = $this->mailgun()->get("lists/{$this->address}/members/{$memberaddress}")->http_response_body->member;

        $member = new Member();
        $member->setMember($result);

        return $member;
    }

    /**
     * Add a new member to this mailinglist
     *
     * @param array $params
     *
     * @return Member
     */
    public function addMember($params = [])
    {
        if (isset($params['vars']) && (is_array($params['vars']) || is_object($params['vars'])) ) {
            $params['vars'] = json_encode($params['vars']);
        }

        $member = new Member();
        $member->setMember(
            $this->mailgun()->post(
                "lists/{$this->address}/members",
                $this->_parseParams($params)
            )->http_response_body->member
        );

        return $member;

    }


    /**
     * Add multiple members to this mailinglist
     *
     * @param array  $members
     * @param bool   $upsert
     *
     * @return Mailinglist
     */
    public function addMembers($members = [], $upsert = false)
    {
        $upsert = ($upsert ? 'yes' : 'no');

        foreach ($members as $key => $member) {
            if (!is_string($member)) {
                $members[$key] = $this->_parseMemberParams($member);
            }
        }

        $mailinglist = new Mailinglist();
        $mailinglist->setList(
            $this->mailgun(true)->post("lists/{$this->address}/members.json", [
                'members' => json_encode($members),
                'upsert' => $upsert
            ])->http_response_body->list
        );

        return $mailinglist;
    }

    /**
     * Update a member in this mailinglist
     *
     * @param $memberaddress
     * @param $params
     *
     * @return Member
     */
    public function updateMember($memberaddress, $params)
    {
        if (isset($params['vars']) && (is_array($params['vars']) || is_object($params['vars'])) ) {
            $params['vars'] = json_encode($params['vars']);
        }

        $member = new Member();
        $member->setMember(
            $this->mailgun()->put(
                "lists/{$this->address}/members/{$memberaddress}",
                $this->_parseParams($params)
            )->http_response_body->member
        );

        return $member;
    }

    /**
     * Delete a member from this mailinglist
     *
     * @param string $memberaddress
     *
     * @return bool
     */
    public function deleteMember($memberaddress)
    {
        $this->mailgun()->delete("lists/{$this->address}/members/{$memberaddress}");
        return true;
    }

    /**
     * @param $member
     *
     * @return mixed
     */
    protected function _parseMemberParams($member)
    {
        if (isset($member['vars']) && is_string($member['vars'])) {
            $member['vars'] = json_decode($member['vars']);
        }
        return $member;
    }

    /**
     * Convert the created_at date to a Carbon instance when accessed
     *
     * @param $name
     *
     * @return Carbon
     */
    public function __get($name)
    {
        if ($name == 'created_at') {
            return $this->parseDate($this->created_at);
        }
    }

    /**
     * Convert given date to a Carbon instance
     *
     * @param $date
     *
     * @return Carbon
     */
    private function parseDate($date)
    {
        return new Carbon($date, Config::get('app.timezone'));
    }

}
