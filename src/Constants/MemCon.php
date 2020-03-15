<?php


namespace App\Constants;


class MemCon
{

    const ORGANIZATION = 'Organization';
    const PERSON = 'Person';
    const CATEGORY = 'Category';

    const TYPES = [
        self::ORGANIZATION,
        self::PERSON,
        self::CATEGORY
    ];

    const ICON_INSTITUTION = 'fi-results-demographics';
    const ICON_PERSON = 'fi-torsos-female-male';
    const ICON_USER = 'fi-torsos-all';
    const ICON_CATEGORY = 'fi-results';

    const ICONS = [
        self::ICON_CATEGORY,
        self::ICON_PERSON,
        self::ICON_INSTITUTION
    ];

    /**
     * originally from MemberViewHelper
     * Value unkown
     *
     * @var array
     */
//    protected $_classMap = [
//        MEMBER_TYPE_ORGANIZATION => 'institution',
//        MEMBER_TYPE_PERSON => 'person',
//        MEMBER_TYPE_USER => 'user',
//        MEMBER_TYPE_CATEGORY => 'category',
//    ];

    /**
     * originally from MemberViewHelper
     * Value unkown
     *
     * @var array
     */
//    protected $_iconMap = [
//        MEMBER_TYPE_ORGANIZATION => ICON_MEMBER_TYPE_INSTITUTION,
//        MEMBER_TYPE_PERSON => ICON_MEMBER_TYPE_PERSON,
//        MEMBER_TYPE_USER => ICON_MEMBER_TYPE_USER,
//        MEMBER_TYPE_CATEGORY => ICON_MEMBER_TYPE_CATEGORY,
//    ];

    /**
     * originally from Controller
     * Value unknown
     *
     * @var array
     */
//    private $_memberTypes = [
//        MEMBER_TYPE_ORGANIZATION, MEMBER_TYPE_ORGANIZATION,
//        MEMBER_TYPE_PERSON, MEMBER_TYPE_PERSON,
//        MEMBER_TYPE_USER, MEMBER_TYPE_USER,
//        MEMBER_TYPE_CATEGORY, MEMBER_TYPE_CATEGORY
//    ];

}
