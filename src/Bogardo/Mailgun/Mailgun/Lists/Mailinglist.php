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

    public function __construct($item)
    {
        $this->name = $item->name;
        $this->address = $item->address;
        $this->description = $item->description;
        $this->members_count = $item->members_count;
        $this->access_level = $item->access_level;
        $this->created_at = $this->parseDate($item->created_at)->format('Y-m-d H:i:s');
    }

    public function __get($name)
    {
        if ($name == 'created_at') {
            return $this->parseDate($this->created_at);
        }
    }

    private function parseDate($date)
    {
        return new Carbon($date, Config::get('app.timezone'));
    }

    public function members($params = [])
    {
        $items = new Collection([]);

        $result = $this->mailgun()->get("lists/{$this->address}/members", $this->_parseParams($params))->http_response_body;

        if ($result) {
            foreach ($result->items as $item) {
                $items->push(new Member($item));
            }
        }

        return $items;
    }

    public function addMember($params = [])
    {

    }

    /**
     * @param string $listaddress
     * @param array  $members
     * @param bool   $upsert
     *
     * @return Mailinglist
     */
    public function addMembers($listaddress, $members = [], $upsert = false)
    {
        $upsert = ($upsert ? 'yes' : 'no');

        foreach ($members as $key => $member) {
            if (!is_string($member)) {
                $members[$key] = $this->_parseMemberParams($member);
            }
        }

        return new Mailinglist(
            $this->mailgun(true)->post("lists/{$listaddress}/members.json", [
                'members' => json_encode($members),
                'upsert' => $upsert
            ])->http_response_body->list
        );
    }

    /**
     * @param string $listaddress
     * @param string $memberaddress
     *
     * @return bool
     */
    public function deleteMember($listaddress, $memberaddress)
    {
        $this->mailgun()->delete("lists/{$listaddress}/members/{$memberaddress}");
        return true;
    }

    protected function _parseMemberParams($member)
    {
        if (isset($member['vars']) && is_string($member['vars'])) {
            $member['vars'] = json_decode($member['vars']);
        }
        return $member;
    }


}
