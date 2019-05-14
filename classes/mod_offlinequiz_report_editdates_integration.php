<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<title>offlinequizdates.php - Offline Quiz [TW] - Redmine for TSC</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="description" content="Redmine" />
<meta name="keywords" content="issue,bug,tracker" />
<meta name="csrf-param" content="authenticity_token" />
<meta name="csrf-token" content="BmQC5mj5+R7U2Qwc2ohwDH/bEPUkA/DmdGnStdfhxeXTSw2vkmuq2yvZFadWpfVep7b1+4IGRFJ1hp8oB6DgqQ==" />
<link rel='shortcut icon' href='/favicon.ico?1457889447' />
<link rel="stylesheet" media="all" href="/stylesheets/jquery/jquery-ui-1.11.0.css?1457889447" />
<link rel="stylesheet" media="all" href="/stylesheets/application.css?1534937054" />
<link rel="stylesheet" media="all" href="/stylesheets/responsive.css?1534937054" />

<script src="/javascripts/jquery-1.11.1-ui-1.11.0-ujs-3.1.4.js?1457889447"></script>
<script src="/javascripts/application.js?1534937054"></script>
<script src="/javascripts/responsive.js?1534937054"></script>
<script>
//<![CDATA[
$(window).load(function(){ warnLeavingUnsaved('The current page contains unsaved text that will be lost if you leave this page.'); });
//]]>
</script>

<link rel="stylesheet" media="screen" href="/plugin_assets/redmine_agile/stylesheets/redmine_agile.css?1529999440" /> <link rel="stylesheet" media="screen" href="/plugin_assets/redmine_banner/stylesheets/banner.css?1528453834" /><script src="/plugin_assets/redmine_banner/javascripts/banner.js?1528453834"></script> <link rel="stylesheet" media="all" href="/plugin_assets/sidebar_hide/stylesheets/sidebar_hide.css?1534863804" /><script src="/plugin_assets/sidebar_hide/javascripts/sidebar_hide.js?1534863804"></script> <script src="/plugin_assets/redmine_checklists/javascripts/checklists.js?1529999440"></script><link rel="stylesheet" media="screen" href="/plugin_assets/redmine_checklists/stylesheets/checklists.css?1529999440" />
<!-- page specific tags -->
    <link rel="stylesheet" media="screen" href="/stylesheets/scm.css?1534937054" /></head>
<body class="project-mod_offlinequiz controller-attachments action-show">

<div id="wrapper">

<div class="flyout-menu js-flyout-menu">


        <div class="flyout-menu__search">
            <form action="/projects/mod_offlinequiz/search" accept-charset="UTF-8" method="get"><input name="utf8" type="hidden" value="&#x2713;" />
            
            <label class="search-magnifier search-magnifier--flyout" for="flyout-search">&#9906;</label>
            <input type="text" name="q" id="flyout-search" class="small js-search-input" placeholder="Search" />
</form>        </div>

        <div class="flyout-menu__avatar ">
                <a href="/users/70"><img alt="" title="" class="gravatar" srcset="//www.gravatar.com/avatar/44e36f55c367c9e5bb965672f691e897?rating=PG&amp;size=160&amp;default= 2x" src="//www.gravatar.com/avatar/44e36f55c367c9e5bb965672f691e897?rating=PG&amp;size=80&amp;default=" /></a>
            <a class="user active" href="/users/70">twedekind</a>
        </div>

        <h3>Project</h3>
        <span class="js-project-menu"></span>

    <h3>General</h3>
    <span class="js-general-menu"></span>

    <span class="js-sidebar flyout-menu__sidebar"></span>

    <h3>Profile</h3>
    <span class="js-profile-menu"></span>

</div>

<div id="wrapper2">
<div id="wrapper3">
<div id="top-menu">
    <div id="account">
        <ul><li><a class="my-account" href="/my/account">My account</a></li><li><a class="logout" rel="nofollow" data-method="post" href="/logout">Sign out</a></li></ul>    </div>
    <div id="loggedas">Logged in as <a class="user active" href="/users/70">twedekind</a></div>
    <ul><li><a class="home" href="/">Home</a></li><li><a class="my-page" href="/my/page">My page</a></li><li><a class="projects" href="/projects">Projects</a></li><li><a class="help" href="https://www.redmine.org/guide">Help</a></li></ul></div>

<div id="header">

    <a href="#" class="mobile-toggle-button js-flyout-menu-toggle-button"></a>

    <div id="quick-search">
        <form action="/projects/mod_offlinequiz/search" accept-charset="UTF-8" method="get"><input name="utf8" type="hidden" value="&#x2713;" />
        <input type="hidden" name="scope" />
        
        <label for='q'>
          <a accesskey="4" href="/projects/mod_offlinequiz/search">Search</a>:
        </label>
        <input type="text" name="q" id="q" size="20" class="small" accesskey="f" />
</form>        <div id="project-jump" class="drdn"><span class="drdn-trigger">Offline Quiz [TW]</span><div class="drdn-content"><div class="quick-search"><input type="text" name="q" id="projects-quick-search" value="" class="autocomplete" data-automcomplete-url="/projects/autocomplete.js?jump=issues" autocomplete="off" /></div><div class="drdn-items projects selection"><a title="Moodle" href="/projects/mdl?jump=issues"><span style="padding-left:0px;">Moodle</span></a><a title="QType Multichoiceset" href="/projects/moodle-questiontype_multichoiceset?jump=issues"><span style="padding-left:16px;">QType Multichoiceset</span></a><a title="general module improvements" href="/projects/general-module-improvements?jump=issues"><span style="padding-left:16px;">general module improvements</span></a><a title="Kreuzerlübung Reporting [DB]" href="/projects/checkmark_reporting?jump=issues"><span style="padding-left:16px;">Kreuzerlübung Reporting [DB]</span></a><a title="Kreuzerlübung [DB]" href="/projects/mod_checkmark?jump=issues"><span style="padding-left:16px;">Kreuzerlübung [DB]</span></a><a title="Offline Quiz [TW]" class="selected" href="/projects/mod_offlinequiz?jump=issues"><span style="padding-left:16px;">Offline Quiz [TW]</span></a><a title="Offline Quiz Cron [TW]" href="/projects/mod-offlinequiz-crom?jump=issues"><span style="padding-left:16px;">Offline Quiz Cron [TW]</span></a><a title="Public Moodle Core Patches" href="/projects/public-moodle-core-patches?jump=issues"><span style="padding-left:16px;">Public Moodle Core Patches</span></a><a title="Studierendenordner [HL]" href="/projects/mod_publication?jump=issues"><span style="padding-left:16px;">Studierendenordner [HL]</span></a><a title="Theme Boost [SN]" href="/projects/theme-university-boost?jump=issues"><span style="padding-left:16px;">Theme Boost [SN]</span></a><a title="Uni Wien Enrolments [TW]" href="/projects/enrol_univie?jump=issues"><span style="padding-left:16px;">Uni Wien Enrolments [TW]</span></a><a title="UW Uniservices - i3v Schnittstelle [TW]" href="/projects/uw-i3v?jump=issues"><span style="padding-left:16px;">UW Uniservices - i3v Schnittstelle [TW]</span></a><a title="Uni Wien – Requests/Bugs/... für Moodle 2.x" href="/projects/uw_requests?jump=issues"><span style="padding-left:0px;">Uni Wien – Requests/Bugs/... für Moodle 2.x</span></a><a title="UW Migrationsskripte" href="/projects/uw-bugs?jump=issues"><span style="padding-left:16px;">UW Migrationsskripte</span></a></div><div class="drdn-items all-projects selection"><a href="/projects?jump=issues">All Projects</a></div></div></div>
    </div>

    <h1><span class="breadcrumbs"><a class="root" href="/projects/mdl?jump=issues">Moodle</a><span class="separator"> &raquo; </span></span><span class="current-project">Offline Quiz [TW]</span></h1>

    <div id="main-menu" class="tabs">
        <ul><li>
<a id="new-object" onclick="toggleNewObjectDropdown(); return false;" class="new-object" href="#"> + </a>
<ul class="menu-children"><li><a accesskey="7" class="new-issue-sub" href="/projects/mod_offlinequiz/issues/new">New issue</a></li><li><a class="new-issue-category" href="/projects/mod_offlinequiz/issue_categories/new">New category</a></li><li><a class="new-timelog" href="/projects/mod_offlinequiz/time_entries/new">Log time</a></li><li><a class="new-wiki-page" href="/projects/mod_offlinequiz/wiki/new">New wiki page</a></li><li><a class="new-file" href="/projects/mod_offlinequiz/files/new">New file</a></li></ul>
</li><li><a class="overview" href="/projects/mod_offlinequiz">Overview</a></li><li><a class="activity" href="/projects/mod_offlinequiz/activity">Activity</a></li><li><a class="roadmap" href="/projects/mod_offlinequiz/roadmap">Roadmap</a></li><li><a class="issues selected" href="/projects/mod_offlinequiz/issues">Issues</a></li><li><a class="time-entries" href="/projects/mod_offlinequiz/time_entries">Spent time</a></li><li><a class="agile" href="/projects/mod_offlinequiz/agile/board">Agile</a></li><li><a class="news" href="/projects/mod_offlinequiz/news">News</a></li><li><a class="documents" href="/projects/mod_offlinequiz/documents">Documents</a></li><li><a class="wiki" href="/projects/mod_offlinequiz/wiki">Wiki</a></li><li><a class="files" href="/projects/mod_offlinequiz/files">Files</a></li><li><a class="repository" href="/projects/mod_offlinequiz/repository">Repository</a></li><li><a class="settings" href="/projects/mod_offlinequiz/settings">Settings</a></li></ul>
        <div class="tabs-buttons" style="display:none;">
            <button class="tab-left" onclick="moveTabLeft(this); return false;"></button>
            <button class="tab-right" onclick="moveTabRight(this); return false;"></button>
        </div>
    </div>
</div>

<div id="main" class="nosidebar">
    <div id="sidebar">
        
        
    </div>

    <div id="content">
        
        <div class="contextual">
  <a class="icon icon-download" href="/attachments/download/5641/offlinequizdates.php">Download (2.78 KB)</a></div>

<h2>offlinequizdates.php</h2>

<div class="attachments">
<p>
   <span class="author"><a class="user active" href="/users/47">Andreas Krieger</a>, 05/09/2019 09:53 AM</span></p>
</div>

  &nbsp;
  <div class="autoscroll">
<table class="filecontent syntaxhl">
<tbody>
  <tr id="L1">
    <th class="line-num">
      <a href="#L1">1</a>
    </th>
    <td class="line-code">
      <pre><span class="inline-delimiter">&lt;?php</span>
</pre>
    </td>
  </tr>
  <tr id="L2">
    <th class="line-num">
      <a href="#L2">2</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// This file is part of Moodle - http://moodle.org/</span>
</pre>
    </td>
  </tr>
  <tr id="L3">
    <th class="line-num">
      <a href="#L3">3</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">//</span>
</pre>
    </td>
  </tr>
  <tr id="L4">
    <th class="line-num">
      <a href="#L4">4</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// Moodle is free software: you can redistribute it and/or modify</span>
</pre>
    </td>
  </tr>
  <tr id="L5">
    <th class="line-num">
      <a href="#L5">5</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// it under the terms of the GNU General Public License as published by</span>
</pre>
    </td>
  </tr>
  <tr id="L6">
    <th class="line-num">
      <a href="#L6">6</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// the Free Software Foundation, either version 3 of the License, or</span>
</pre>
    </td>
  </tr>
  <tr id="L7">
    <th class="line-num">
      <a href="#L7">7</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// (at your option) any later version.</span>
</pre>
    </td>
  </tr>
  <tr id="L8">
    <th class="line-num">
      <a href="#L8">8</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">//</span>
</pre>
    </td>
  </tr>
  <tr id="L9">
    <th class="line-num">
      <a href="#L9">9</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// Moodle is distributed in the hope that it will be useful,</span>
</pre>
    </td>
  </tr>
  <tr id="L10">
    <th class="line-num">
      <a href="#L10">10</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// but WITHOUT ANY WARRANTY; without even the implied warranty of</span>
</pre>
    </td>
  </tr>
  <tr id="L11">
    <th class="line-num">
      <a href="#L11">11</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the</span>
</pre>
    </td>
  </tr>
  <tr id="L12">
    <th class="line-num">
      <a href="#L12">12</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// GNU General Public License for more details.</span>
</pre>
    </td>
  </tr>
  <tr id="L13">
    <th class="line-num">
      <a href="#L13">13</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">//</span>
</pre>
    </td>
  </tr>
  <tr id="L14">
    <th class="line-num">
      <a href="#L14">14</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// You should have received a copy of the GNU General Public License</span>
</pre>
    </td>
  </tr>
  <tr id="L15">
    <th class="line-num">
      <a href="#L15">15</a>
    </th>
    <td class="line-code">
      <pre><span class="comment">// along with Moodle.  If not, see &lt;http://www.gnu.org/licenses/&gt;..</span>
</pre>
    </td>
  </tr>
  <tr id="L16">
    <th class="line-num">
      <a href="#L16">16</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L17">
    <th class="line-num">
      <a href="#L17">17</a>
    </th>
    <td class="line-code">
      <pre><span class="predefined">defined</span>(<span class="string"><span class="delimiter">'</span><span class="content">MOODLE_INTERNAL</span><span class="delimiter">'</span></span>) || <span class="predefined">die</span>;
</pre>
    </td>
  </tr>
  <tr id="L18">
    <th class="line-num">
      <a href="#L18">18</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L19">
    <th class="line-num">
      <a href="#L19">19</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L20">
    <th class="line-num">
      <a href="#L20">20</a>
    </th>
    <td class="line-code">
      <pre><span class="predefined">require_once</span>(<span class="local-variable">$CFG</span>-&gt;dirroot.<span class="string"><span class="delimiter">'</span><span class="content">/mod/offlinequiz/lib.php</span><span class="delimiter">'</span></span>);
</pre>
    </td>
  </tr>
  <tr id="L21">
    <th class="line-num">
      <a href="#L21">21</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L22">
    <th class="line-num">
      <a href="#L22">22</a>
    </th>
    <td class="line-code">
      <pre><span class="keyword">class</span> <span class="class">report_editdates_mod_offlinequiz_date_extractor</span>
</pre>
    </td>
  </tr>
  <tr id="L23">
    <th class="line-num">
      <a href="#L23">23</a>
    </th>
    <td class="line-code">
      <pre>        <span class="keyword">extends</span> report_editdates_mod_date_extractor {
</pre>
    </td>
  </tr>
  <tr id="L24">
    <th class="line-num">
      <a href="#L24">24</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L25">
    <th class="line-num">
      <a href="#L25">25</a>
    </th>
    <td class="line-code">
      <pre>    <span class="keyword">public</span> <span class="keyword">function</span> <span class="function">__construct</span>(<span class="local-variable">$course</span>) {
</pre>
    </td>
  </tr>
  <tr id="L26">
    <th class="line-num">
      <a href="#L26">26</a>
    </th>
    <td class="line-code">
      <pre>        <span class="predefined-constant">parent</span>::__construct(<span class="local-variable">$course</span>, <span class="string"><span class="delimiter">'</span><span class="content">offlinequiz</span><span class="delimiter">'</span></span>);
</pre>
    </td>
  </tr>
  <tr id="L27">
    <th class="line-num">
      <a href="#L27">27</a>
    </th>
    <td class="line-code">
      <pre>        <span class="predefined-constant">parent</span>::load_data();
</pre>
    </td>
  </tr>
  <tr id="L28">
    <th class="line-num">
      <a href="#L28">28</a>
    </th>
    <td class="line-code">
      <pre>    }
</pre>
    </td>
  </tr>
  <tr id="L29">
    <th class="line-num">
      <a href="#L29">29</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L30">
    <th class="line-num">
      <a href="#L30">30</a>
    </th>
    <td class="line-code">
      <pre>    <span class="keyword">public</span> <span class="keyword">function</span> <span class="function">get_settings</span>(cm_info <span class="local-variable">$cm</span>) {
</pre>
    </td>
  </tr>
  <tr id="L31">
    <th class="line-num">
      <a href="#L31">31</a>
    </th>
    <td class="line-code">
      <pre>        <span class="local-variable">$offlinequiz</span> = <span class="local-variable">$this</span>-&gt;mods[<span class="local-variable">$cm</span>-&gt;instance];
</pre>
    </td>
  </tr>
  <tr id="L32">
    <th class="line-num">
      <a href="#L32">32</a>
    </th>
    <td class="line-code">
      <pre>        <span class="keyword">return</span> <span class="predefined">array</span>(<span class="string"><span class="delimiter">'</span><span class="content">time</span><span class="delimiter">'</span></span>      =&gt; <span class="keyword">new</span> report_editdates_date_setting(
</pre>
    </td>
  </tr>
  <tr id="L33">
    <th class="line-num">
      <a href="#L33">33</a>
    </th>
    <td class="line-code">
      <pre>                                        get_string(<span class="string"><span class="delimiter">'</span><span class="content">quizdate</span><span class="delimiter">'</span></span>, <span class="string"><span class="delimiter">'</span><span class="content">offlinequiz</span><span class="delimiter">'</span></span>),
</pre>
    </td>
  </tr>
  <tr id="L34">
    <th class="line-num">
      <a href="#L34">34</a>
    </th>
    <td class="line-code">
      <pre>                                        <span class="local-variable">$offlinequiz</span>-&gt;<span class="predefined">time</span>, <span class="predefined-constant">self</span>::<span class="constant">DATETIME</span>, <span class="predefined-constant">true</span>, <span class="integer">1</span>),
</pre>
    </td>
  </tr>
  <tr id="L35">
    <th class="line-num">
      <a href="#L35">35</a>
    </th>
    <td class="line-code">
      <pre>                     <span class="string"><span class="delimiter">'</span><span class="content">timeopen</span><span class="delimiter">'</span></span>  =&gt; <span class="keyword">new</span> report_editdates_date_setting(
</pre>
    </td>
  </tr>
  <tr id="L36">
    <th class="line-num">
      <a href="#L36">36</a>
    </th>
    <td class="line-code">
      <pre>                                        get_string(<span class="string"><span class="delimiter">'</span><span class="content">reviewopens</span><span class="delimiter">'</span></span>, <span class="string"><span class="delimiter">'</span><span class="content">offlinequiz</span><span class="delimiter">'</span></span>),
</pre>
    </td>
  </tr>
  <tr id="L37">
    <th class="line-num">
      <a href="#L37">37</a>
    </th>
    <td class="line-code">
      <pre>                                        <span class="local-variable">$offlinequiz</span>-&gt;timeopen, <span class="predefined-constant">self</span>::<span class="constant">DATETIME</span>, <span class="predefined-constant">true</span>, <span class="integer">1</span>),
</pre>
    </td>
  </tr>
  <tr id="L38">
    <th class="line-num">
      <a href="#L38">38</a>
    </th>
    <td class="line-code">
      <pre>                     <span class="string"><span class="delimiter">'</span><span class="content">timeclose</span><span class="delimiter">'</span></span> =&gt; <span class="keyword">new</span> report_editdates_date_setting(
</pre>
    </td>
  </tr>
  <tr id="L39">
    <th class="line-num">
      <a href="#L39">39</a>
    </th>
    <td class="line-code">
      <pre>                                        get_string(<span class="string"><span class="delimiter">'</span><span class="content">reviewcloses</span><span class="delimiter">'</span></span>, <span class="string"><span class="delimiter">'</span><span class="content">offlinequiz</span><span class="delimiter">'</span></span>),
</pre>
    </td>
  </tr>
  <tr id="L40">
    <th class="line-num">
      <a href="#L40">40</a>
    </th>
    <td class="line-code">
      <pre>                                        <span class="local-variable">$offlinequiz</span>-&gt;timeclose, <span class="predefined-constant">self</span>::<span class="constant">DATETIME</span>, <span class="predefined-constant">true</span>, <span class="integer">1</span>)
</pre>
    </td>
  </tr>
  <tr id="L41">
    <th class="line-num">
      <a href="#L41">41</a>
    </th>
    <td class="line-code">
      <pre>        );
</pre>
    </td>
  </tr>
  <tr id="L42">
    <th class="line-num">
      <a href="#L42">42</a>
    </th>
    <td class="line-code">
      <pre>    }
</pre>
    </td>
  </tr>
  <tr id="L43">
    <th class="line-num">
      <a href="#L43">43</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L44">
    <th class="line-num">
      <a href="#L44">44</a>
    </th>
    <td class="line-code">
      <pre>    <span class="keyword">public</span> <span class="keyword">function</span> <span class="function">validate_dates</span>(cm_info <span class="local-variable">$cm</span>, <span class="predefined">array</span> <span class="local-variable">$dates</span>) {
</pre>
    </td>
  </tr>
  <tr id="L45">
    <th class="line-num">
      <a href="#L45">45</a>
    </th>
    <td class="line-code">
      <pre>        <span class="local-variable">$errors</span> = <span class="predefined">array</span>();
</pre>
    </td>
  </tr>
  <tr id="L46">
    <th class="line-num">
      <a href="#L46">46</a>
    </th>
    <td class="line-code">
      <pre>        <span class="keyword">if</span> (<span class="local-variable">$dates</span>[<span class="string"><span class="delimiter">'</span><span class="content">timeopen</span><span class="delimiter">'</span></span>] != <span class="integer">0</span> &amp;&amp; <span class="local-variable">$dates</span>[<span class="string"><span class="delimiter">'</span><span class="content">timeclose</span><span class="delimiter">'</span></span>] != <span class="integer">0</span>
</pre>
    </td>
  </tr>
  <tr id="L47">
    <th class="line-num">
      <a href="#L47">47</a>
    </th>
    <td class="line-code">
      <pre>                &amp;&amp; <span class="local-variable">$dates</span>[<span class="string"><span class="delimiter">'</span><span class="content">timeclose</span><span class="delimiter">'</span></span>] &lt; <span class="local-variable">$dates</span>[<span class="string"><span class="delimiter">'</span><span class="content">timeopen</span><span class="delimiter">'</span></span>]) {
</pre>
    </td>
  </tr>
  <tr id="L48">
    <th class="line-num">
      <a href="#L48">48</a>
    </th>
    <td class="line-code">
      <pre>            <span class="local-variable">$errors</span>[<span class="string"><span class="delimiter">'</span><span class="content">timeclose</span><span class="delimiter">'</span></span>] = get_string(<span class="string"><span class="delimiter">'</span><span class="content">closebeforeopen</span><span class="delimiter">'</span></span>, <span class="string"><span class="delimiter">'</span><span class="content">offlinequiz</span><span class="delimiter">'</span></span>);
</pre>
    </td>
  </tr>
  <tr id="L49">
    <th class="line-num">
      <a href="#L49">49</a>
    </th>
    <td class="line-code">
      <pre>        }
</pre>
    </td>
  </tr>
  <tr id="L50">
    <th class="line-num">
      <a href="#L50">50</a>
    </th>
    <td class="line-code">
      <pre>        <span class="keyword">return</span> <span class="local-variable">$errors</span>;
</pre>
    </td>
  </tr>
  <tr id="L51">
    <th class="line-num">
      <a href="#L51">51</a>
    </th>
    <td class="line-code">
      <pre>    }
</pre>
    </td>
  </tr>
  <tr id="L52">
    <th class="line-num">
      <a href="#L52">52</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L53">
    <th class="line-num">
      <a href="#L53">53</a>
    </th>
    <td class="line-code">
      <pre>    <span class="keyword">public</span> <span class="keyword">function</span> <span class="function">save_dates</span>(cm_info <span class="local-variable">$cm</span>, <span class="predefined">array</span> <span class="local-variable">$dates</span>) {
</pre>
    </td>
  </tr>
  <tr id="L54">
    <th class="line-num">
      <a href="#L54">54</a>
    </th>
    <td class="line-code">
      <pre>        <span class="predefined-constant">parent</span>::save_dates(<span class="local-variable">$cm</span>, <span class="local-variable">$dates</span>);
</pre>
    </td>
  </tr>
  <tr id="L55">
    <th class="line-num">
      <a href="#L55">55</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L56">
    <th class="line-num">
      <a href="#L56">56</a>
    </th>
    <td class="line-code">
      <pre>        <span class="comment">// Fetch module instance from $mods array.</span>
</pre>
    </td>
  </tr>
  <tr id="L57">
    <th class="line-num">
      <a href="#L57">57</a>
    </th>
    <td class="line-code">
      <pre>        <span class="local-variable">$offlinequiz</span> = <span class="local-variable">$this</span>-&gt;mods[<span class="local-variable">$cm</span>-&gt;instance];
</pre>
    </td>
  </tr>
  <tr id="L58">
    <th class="line-num">
      <a href="#L58">58</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L59">
    <th class="line-num">
      <a href="#L59">59</a>
    </th>
    <td class="line-code">
      <pre>        <span class="local-variable">$offlinequiz</span>-&gt;instance = <span class="local-variable">$cm</span>-&gt;instance;
</pre>
    </td>
  </tr>
  <tr id="L60">
    <th class="line-num">
      <a href="#L60">60</a>
    </th>
    <td class="line-code">
      <pre>        <span class="local-variable">$offlinequiz</span>-&gt;coursemodule = <span class="local-variable">$cm</span>-&gt;id;
</pre>
    </td>
  </tr>
  <tr id="L61">
    <th class="line-num">
      <a href="#L61">61</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L62">
    <th class="line-num">
      <a href="#L62">62</a>
    </th>
    <td class="line-code">
      <pre>        <span class="comment">// Updating date values.</span>
</pre>
    </td>
  </tr>
  <tr id="L63">
    <th class="line-num">
      <a href="#L63">63</a>
    </th>
    <td class="line-code">
      <pre>        <span class="keyword">foreach</span> (<span class="local-variable">$dates</span> <span class="keyword">as</span> <span class="local-variable">$datetype</span> =&gt; <span class="local-variable">$datevalue</span>) {
</pre>
    </td>
  </tr>
  <tr id="L64">
    <th class="line-num">
      <a href="#L64">64</a>
    </th>
    <td class="line-code">
      <pre>            <span class="local-variable">$offlinequiz</span>-&gt;<span class="local-variable">$datetype</span> = <span class="local-variable">$datevalue</span>;
</pre>
    </td>
  </tr>
  <tr id="L65">
    <th class="line-num">
      <a href="#L65">65</a>
    </th>
    <td class="line-code">
      <pre>        }
</pre>
    </td>
  </tr>
  <tr id="L66">
    <th class="line-num">
      <a href="#L66">66</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L67">
    <th class="line-num">
      <a href="#L67">67</a>
    </th>
    <td class="line-code">
      <pre>        <span class="comment">// Calling the update event method to change the calender events accordingly.</span>
</pre>
    </td>
  </tr>
  <tr id="L68">
    <th class="line-num">
      <a href="#L68">68</a>
    </th>
    <td class="line-code">
      <pre>        offlinequiz_update_events(<span class="local-variable">$offlinequiz</span>);
</pre>
    </td>
  </tr>
  <tr id="L69">
    <th class="line-num">
      <a href="#L69">69</a>
    </th>
    <td class="line-code">
      <pre>        offlinequiz_grade_item_update(<span class="local-variable">$offlinequiz</span>);
</pre>
    </td>
  </tr>
  <tr id="L70">
    <th class="line-num">
      <a href="#L70">70</a>
    </th>
    <td class="line-code">
      <pre>
</pre>
    </td>
  </tr>
  <tr id="L71">
    <th class="line-num">
      <a href="#L71">71</a>
    </th>
    <td class="line-code">
      <pre>    }
</pre>
    </td>
  </tr>
  <tr id="L72">
    <th class="line-num">
      <a href="#L72">72</a>
    </th>
    <td class="line-code">
      <pre>}
</pre>
    </td>
  </tr>
</tbody>
</table>
</div>





          
        <div style="clear:both;"></div>
    </div>
</div>
</div>

<div id="ajax-indicator" style="display:none;"><span>Loading...</span></div>
<div id="ajax-modal" style="display:none;"></div>

<div id="footer">
  <div class="bgl"><div class="bgr">
    Powered by <a href="https://www.redmine.org/">Redmine</a> &copy; 2006-2018 Jean-Philippe Lang
  </div></div>
</div>
</div>
</div>
 
</body>
</html>
