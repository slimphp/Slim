<p>
	Lorem ipsum dolor sit amet, {{ $firstVariable }} adipisicing elit. Repellendus, minima, excepturi nostrum nobis suscipit deserunt repudiandae nesciunt distinctio debitis iure quam ducimus molestiae ex aperiam blanditiis reprehenderit cupiditate tempora sit.
</p>

<p>
@if($secondVariable == 100)
	If Block Passed Successfully
@endif
</p>

<p>
<p>
	Diving into If else block
</p>
@if($thirdVariable == 100)
	Third variable is equals to 100
@else
	Can you see me ? I am inside else block
@endif
</p>

<p>
@if($fourthVariable == 100)
	Checking out the if elseif else block
@elseif($fourthVariable == 400)
	I am a condition inside elseif block
@else
	Elseif condition was not true here
@endif
</p>

<p>
@for($someVar = $forCount; $someVar < $forCap; $someVar++)
	Check it out. For loop at iteration {{ $someVar }}
@endfor
</p>

<p>
@while($whilevar)
	Looping inside while: iteration {{ $whilevar-- }}
@endwhile
</p>

Trying to include another template here

@include('otherTest')