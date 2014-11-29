<?php  namespace Bogardo\Mailgun\Lists;


use Bogardo\Mailgun\MailgunApi;
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
     * @return bool
     */
    public function delete()
    {
        $this->mailgun()->delete("lists/{$this->address}");
        return true;
    }

    /**
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
            $this->mailgun()->post("lists/{$this->address}/members",
            $this->_parseParams($params))->http_response_body->member
        );

        return $member;

    }

    /**
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
     * @param $date
     *
     * @return Carbon
     */
    private function parseDate($date)
    {
        return new Carbon($date, Config::get('app.timezone'));
    }

}
