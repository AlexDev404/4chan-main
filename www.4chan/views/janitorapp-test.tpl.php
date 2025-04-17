<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Janitor Applications - 4chan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/yui.8.css">
  <?php if (IS_4CHANNEL): ?>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/global_blue.1.css">
  <?php else: ?>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/global.59.css">
  <?php endif ?>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/generic.1.css">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script>
    function run() {
      if (!document.getElementById('application-form')) {
        return;
      }
      
      var hd = -(0 | (new Date().getTimezoneOffset() / 60));
      
      var sel = document.getElementById('js-tz')
      
      if (sel.hasAttribute('data-edit')) {
        return;
      }
      
      var i, el;
      
      for (i = 0; el = sel.options[i]; ++i) {
        if (hd == el.value) {
          sel.selectedIndex = i;
          return;
        }
      }
    }
    
    document.addEventListener('DOMContentLoaded', run, false);
  </script>
  <style type="text/css">
    input, select, textarea {
      height: 22px;
      padding: 1px 2px;
      box-sizing: border-box;
    }
    
    input[type="text"], input[type="email"], select {
      width: 255px;
    }
    
    input[type="number"] {
      width: 50px;
    }
    
    textarea {
      max-width: 100%;
      min-width: 100%;
      min-height: 100px;
      height: 120px;
    }
    
    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border: 1px solid #EEAA88;
    }

    input,
    select,
    textarea {
      border: 1px solid #AAA;
    }

    label {
      display: block;
      font-weight: bold;
      margin-top: 15px;
      margin-bottom: 2px;
    }
    
    .closed-msg {
      font-weight: bold;
      color: red;
      margin-bottom: 10px;
      font-size: 16px;
    }
    
    #captcha-cnt {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
<div id="doc">
<div id="hd">
  <div id="logo-fp">
    <a href="//www.<?php echo WEB_DOMAIN ?>/" title="Home"><img alt="4chan" src="//s.4cdn.org/image/fp/logo-transparent.png" width="300" height="120"></a>
  </div>
</div>
<div id="content">
  <div class="box-outer top-box">
    <div class="box-inner">
      <div class="boxbar">
        <h2>Janitor Applications</h2>
      </div>
      <div class="boxcontent">
        <p>Thank you for expressing interest in becoming a 4chan volunteer janitor.</p>
        <p>Janitors are tasked with keeping the imageboards free of rule-breaking content. Janitors are able to view the reports queue, delete posts, and submit ban and warn requests.</p>
        <span style='color:green; font-weight:bold;'><strong>Janitors CAN...</strong></span>
        <ul>
          <li>Remove threads, replies and images on their <em>assigned</em> board(s).</li>
          <li>Submit ban and warn <em>requests</em> to moderators.</li>
          <li>Access the reports queue to more easily respond to rule-breaking content.</li>
        </ul>
        <span style='color:red; font-weight:bold;'><strong>Janitors CANNOT...</strong></span>
        <ul>
          <li>Ban users.</li>
          <li>Access any administrative functions.</li>
          <li>Use capcodes, make "official" posts, or set rules.</li>
          <li>See user IP addresses or hostnames.</li>
        </ul>
        <span style='color:green; font-weight:bold;'><strong>Janitors ARE...</strong></span>
        <ul>
          <li>Volunteers: Janitors are uncompensated volunteers who should be genuinely interested in the betterment of their board, and the site as a whole.</li>
          <li>Anonymous: Janitors are not to disclose their position nor present themselves in an official capacity or share any private information about the team or site's users. Doing so will lead to immediate termination.</li>
          <li>Versed: Knowledgeable about the content posted to their respective board. Active members of the community.</li>
          <li>Limited: Janitors can only delete from one board to start. Over time, janitors may be eligible to select additional boards. Janitors are to focus on their assigned board(s).</li>
          <li>Accountable: All moderation actions are logged and reviewed.</li>
          <li>Active: Inactive janitors will be removed and replaced.</li>
          <li>Professional: All new janitors are required to go through an orientation, evaluation, and review process.</li>
          <li>Savvy: Proficient with computers. Janitors should own a computer for their own private use (i.e., not a public or shared computer). Janitors need to have a basic understanding of Discord.</li>
        </ul>
        <span style='color:red; font-weight:bold;'><strong>Janitors ARE NOT...</strong></span>
        <ul>
          <li>Moderators: Janitors are not full Team 4chan members.</li>
          <li>Interpreters: Rules are to be enforced as written. Janitors enforce site policy only and personal motives should never influence deletion. They are objective, not subjective.</li>
          <li>Rogues: Janitors are expected to maintain regular contact with the moderation team via Discord or e-mail. Janitors should be present in the Discord channel whenever they are performing janitor tasks.</li>
        </ul>
        <span style='color:green; font-weight:bold;'><strong>Benefits of being a Janitor</strong></span>
        <ul>
          <li>You can take direct action against problems facing your board and your community.</li>
          <li>You have direct access to moderators to ask questions and ask for additional help.</li>
          <li>You have the benefits of a free 4chan Pass for the duration of your time as a janitor (e.g., no captchas, reduced cooldowns, bypass rangebans, vote in contests, etc).</li>
        </ul>
        <span style='color:red; font-weight:bold;'><strong>Drawbacks of being a Janitor</strong></span>
        <ul>
          <li>You do it <span style='font-weight:bold; font-style:italic'>for free</span>.</li>
        </ul>
        <p>Please understand all of the above before applying. We are seeking committed volunteers only, and take this process seriously&mdash;garbage/wasteful/joke applications will be thrown out and you may be banned. Applications are voted on by moderators but final approval is left to the managing moderator and administrator. The process is blind: we cannot
        see your name, handle, or e-mail during the initial voting stages.</p>
        <p>You will only be contacted if you are selected. Applicants who are not selected will <em>not</em> be notified. Selected applicants may receive questions via e-mail and are expected to respond in a timely fashion or their application will be forfeit. All selected applicants must go through an orientation process via Discord, and pass an evaluation to receive final approval. A strong grasp of the English language is required since all orientation and communication is conducted in English. All accepted janitors must also read and agree to the 4chan Volunteer Moderator Agreement, which is a legally binding agreement. There is no timeframe for this process to be completed. <strong>Do not e-mail us to request the status of your application.</strong></p>
        <p>Good luck! Remember: The more detailed you are, the better. Don't be afraid of writing too much&mdash;be concerned with writing too <em>little</em>. Last time around, almost everybody who made it past the initial application stage had written at least one or two well thought out paragraphs.</p>
      </div>
    </div>
  </div>
  <div class="box-outer top-box">
    <div class="box-inner">
      <div class="boxbar">
        <h2>Application Form</h2>
      </div>
      <div class="boxcontent">
      <?php if (APPLICATIONS_OPEN): ?>
        <?php if (!$this->is_desktop_browser()): ?>
        <div class="closed-msg">
          <p><?php echo self::ERR_NOT_DESKTOP ?></p>
        </div>
        <?php elseif ($this->need_auth_email): ?>
        <form action="<?php echo self::WEB_ROOT ?>?id=<?php echo htmlspecialchars($this->auth_uid, ENT_QUOTES) ?>" method="post">
          <label>To edit this application, enter the E-Mail you used to submit it</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="email" name="auth_email" required>
          <div id="captcha-cnt"><label>Verification:</label>
          <?php echo captcha_form(true) ?>
          </div>
          <button type="submit">Submit</button>
        </form>
        <?php else: ?>
        <form id="application-form" action="<?php echo self::WEB_ROOT ?>" method="post">
          <label>First Name</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="text" name="firstname"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['firstname'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Online Nickname / Handle</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="text" name="handle"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['handle'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Email (must be valid)</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="email" name="email"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['email'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Age</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" min="1" type="number" name="age"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['age'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Timezone</label>
          <select name="tz" id="js-tz"<?php if ($this->application) { echo ' data-edit'; } ?>>
          <?php for ($i = -12; $i < 14; $i++): ?>
            <option<?php if ($this->application && $this->application['tz'] == $i): ?> selected<?php endif ?> value="<?php echo $i ?>">UTC<?php echo $i >= 0 ? "+$i" : $i ?><?php if (isset($this->tz_names[$i])): ?> (<?php echo $this->tz_names[$i] ?>)<?php endif ?></option>
          <?php endfor ?>
          </select>
          <label>Hours per day spent browsing 4chan</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="number" name="hours" min="1" max="24"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['hours'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Hours available for janitoring (time frames in your local timezone, ie. 4pm-9pm)</label>
          <input maxlength="<?php echo self::MAX_FIELD_LENGTH ?>" type="text" name="times"<?php if ($this->application): ?> value="<?php echo htmlspecialchars($this->application['times'], ENT_QUOTES) ?>"<?php endif ?> required>
          <label>Board applying for</label>
          <select name="board1" required>
            <option value=""></option>
          <?php foreach ($this->valid_boards as $board => $_): ?>
            <option<?php if ($this->application && $this->application['board1'] == $board): ?> selected<?php endif ?> value="<?php echo $board ?>">/<?php echo $board ?>/</option>
          <?php endforeach ?>
          </select>
          <label>Second choice (optional)</label>
          <select name="board2">
            <option value=""></option>
          <?php foreach ($this->valid_boards as $board => $_): ?>
            <option<?php if ($this->application && $this->application['board2'] == $board): ?> selected<?php endif ?> value="<?php echo $board ?>">/<?php echo $board ?>/</option>
          <?php endforeach ?>
          </select>
          <label><?php echo self::STR_Q1 ?></label>
          <textarea maxlength="<?php echo self::MAX_TXT_FIELD_LENGTH ?>" name="q1" required><?php if ($this->application) echo htmlspecialchars($this->application['q1'], ENT_QUOTES) ?></textarea>
          <label><?php echo self::STR_Q2 ?></label>
          <textarea maxlength="<?php echo self::MAX_TXT_FIELD_LENGTH ?>" name="q2" required><?php if ($this->application) echo htmlspecialchars($this->application['q2'], ENT_QUOTES) ?></textarea>
          <label><?php echo self::STR_Q3 ?></label>
          <textarea maxlength="<?php echo self::MAX_TXT_FIELD_LENGTH ?>" name="q3" required><?php if ($this->application) echo htmlspecialchars($this->application['q3'], ENT_QUOTES) ?></textarea>
          <label><?php echo self::STR_Q4 ?></label>
          <textarea maxlength="<?php echo self::MAX_TXT_FIELD_LENGTH ?>" name="q4" required><?php if ($this->application) echo htmlspecialchars($this->application['q4'], ENT_QUOTES) ?></textarea>
          <div id="captcha-cnt"><label>Verification:</label>
          <?php echo captcha_form(true) ?>
          </div><input type="hidden" name="_cf_fuid" value="<?php echo $this->generate_token() ?>"><?php if ($this->app_uid): ?><input type="hidden" name="id" value="<?php echo $this->app_uid ?>"><input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($this->auth_email, ENT_QUOTES) ?>"><?php endif ?>
          <button type="submit" name="action" value="submit">Submit</button>
        </form>
        <?php endif ?>
      <?php else: ?>
        <div class="closed-msg">
          <p>We are not accepting applications this time.</p>
          <p>An announcement will be posted on the boards when we begin accepting them.</p>
        </div>
      <?php endif ?>
      </div>
    </div>
  </div>
</div>
</div>
</body>
</html>
