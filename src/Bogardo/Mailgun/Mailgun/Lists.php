<?php namespace Bogardo\Mailgun;


use Bogardo\Mailgun\Lists\Mailinglist;
use Bogardo\Mailgun\Lists\Member;
use Illuminate\Support\Collection;

class Lists extends MailgunApi {


    /**
     * @return Collection
     */
    public function all()
    {
        $items = new Collection([]);

        $results = $this->mailgun()->get("lists")->http_response_body;
        if ($results) {
            foreach ($results->items as $item) {
                $items->push(new Mailinglist($item));
            }
        }

        return $items;
    }

    /**
     * @param string $listaddress
     *
     * @return \Bogardo\Mailgun\Lists\Mailinglist
     */
    public function get($listaddress)
    {
        $result = $this->mailgun()->get("lists/{$listaddress}")->http_response_body;
        if ($result) {
            return new Mailinglist($result->list);
        }
    }

    /**
     * @param       $listaddress
     * @param array $params
     *
     * @return Collection
     */
    public function members($listaddress, $params = [])
    {
        $items = new Collection([]);

        $result = $this->mailgun()->get("lists/{$listaddress}/members", $this->_parseParams($params))->http_response_body;

        if ($result) {
            foreach ($result->items as $item) {
                $items->push(new Member($item));
            }
        }

        return $items;
    }

    /**
     * @param array $params
     *
     * @return Mailinglist
     */
    public function create($params = [])
    {
        return new Mailinglist($this->mailgun()->post("lists", $params)->http_response_body->list);
    }

    /**
     * @param string $listaddress
     * @param array  $params
     *
     * @return Mailinglist
     */
    public function update($listaddress, $params = [])
    {
        return new Mailinglist($this->mailgun()->put("lists/{$listaddress}", $params)->http_response_body->list);
    }

    /**
     * @param string $listaddress
     *
     * @return bool
     */
    public function delete($listaddress)
    {
        $this->mailgun()->delete("lists/{$listaddress}");
        return true;
    }

    /**
     * @param string $listaddress
     * @param array  $params
     *
     * @return \Bogardo\Mailgun\Lists\Member
     */
    public function addMember($listaddress, $params = [])
    {
        if (isset($params['vars']) && (is_array($params['vars']) || is_object($params['vars'])) ) {
            $params['vars'] = json_encode($params['vars']);
        }

        return new Member(
            $this->mailgun()->post("lists/{$listaddress}/members",
            $this->_parseParams($params))->http_response_body->member
        );
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
